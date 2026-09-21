<?php
namespace App\Modules\Monitoring\Infrastructure\Persistence;
use App\Models\CustomerNotification;
use App\Models\CustomerOrder;
use App\Models\OperationalAlert;
use App\Models\OperationalAlertNotification;
use App\Models\OrderMonitoringSetting;
use App\Modules\Monitoring\Domain\Contracts\MonitoringRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
final class EloquentMonitoringRepository implements MonitoringRepositoryInterface
{
    private const DEFAULTS=['review_overdue'=>1,'processing_overdue'=>2,'shipment_no_update'=>5,'delivery_overdue'=>2,'settlement_missing'=>3,'return_settlement_missing'=>3];
    public function settings(): array { foreach(self::DEFAULTS as $type=>$days) OrderMonitoringSetting::query()->firstOrCreate(['rule_type'=>$type],['days'=>$days,'is_enabled'=>true]); return OrderMonitoringSetting::query()->orderBy('id')->get()->toArray(); }
    public function saveSetting(string $type,int $days,bool $enabled): array { $setting=OrderMonitoringSetting::query()->updateOrCreate(['rule_type'=>$type],['days'=>$days,'is_enabled'=>$enabled]); DB::table('audit_logs')->insert(['actor_id'=>auth()->id(),'action'=>'order_monitoring_setting_updated','target_type'=>OrderMonitoringSetting::class,'target_id'=>$setting->id,'metadata'=>json_encode(['rule_type'=>$type,'days'=>$days,'is_enabled'=>$enabled]),'created_at'=>now(),'updated_at'=>now()]); return $setting->toArray(); }
    public function detect(): array {
        $settings=collect($this->settings())->keyBy('rule_type'); $created=0; $resolved=0;
        CustomerOrder::query()->with(['review','shipments.events'])->chunkById(100,function($orders)use($settings,&$created,&$resolved):void{ foreach($orders as $order){ $candidates=$this->candidates($order,$settings); $active=OperationalAlert::query()->where('order_id',$order->id)->where('status','!=','resolved')->get(); foreach($candidates as $candidate){ $alert=$active->firstWhere('type',$candidate['type']); if(!$alert){$alert=OperationalAlert::query()->create(['order_id'=>$order->id,'type'=>$candidate['type'],'severity'=>$candidate['severity'],'status'=>'open','detected_at'=>now(),'metadata'=>$candidate['metadata']]);$created++;$this->notify($alert,$order);} else {$alert->update(['severity'=>$candidate['severity'],'metadata'=>$candidate['metadata']]);} } foreach($active as $alert){if(!collect($candidates)->contains('type',$alert->type)){ $alert->update(['status'=>'resolved','resolved_at'=>now()]);$resolved++; }} } }); return ['created'=>$created,'resolved'=>$resolved];
    }
    private function candidates(CustomerOrder $order,$settings):array { $out=[]; $now=now(); $add=function(string $type,Carbon $since,string $severity='medium',array $metadata=[])use(&$out,$settings,$now):void{$rule=$settings->get($type);if(!$rule||!$rule['is_enabled']||$since->copy()->addDays((int)$rule['days'])->isFuture())return;$out[]=['type'=>$type,'severity'=>$now->diffInDays($since)>((int)$rule['days']*2)?'high':$severity,'metadata'=>array_merge($metadata,['since'=>$since->toISOString(),'days_overdue'=>max(0,$now->diffInDays($since)-(int)$rule['days'])])];};
        if($order->status==='reviewing'&&$order->review?->started_at)$add('review_overdue',$order->review->started_at,'high');
        if($order->status==='processing'&&$order->shipments->isEmpty())$add('processing_overdue',$order->updated_at,'high');
        foreach($order->shipments as $shipment){$last=$shipment->events->sortByDesc('created_at')->first();$since=$last?->created_at??$shipment->created_at;if(in_array($shipment->status,['picked_up','in_transit','out_for_delivery'],true))$add('shipment_no_update',$since,'high',['shipment_id'=>$shipment->id,'shipment_status'=>$shipment->status]);if($shipment->status==='delivered')$add('delivery_overdue',$last?->created_at??$shipment->updated_at,'medium',['shipment_id'=>$shipment->id]);}
        return $out;
    }
    private function notify(OperationalAlert $alert,CustomerOrder $order):void { $users=\App\Models\User::query()->whereHas('roles',fn($q)=>$q->whereIn('slug',['admin','manager','order_manager']))->pluck('id'); foreach($users as $userId){if(OperationalAlertNotification::query()->where(['operational_alert_id'=>$alert->id,'user_id'=>$userId])->exists())continue; CustomerNotification::query()->create(['user_id'=>$userId,'type'=>'operational_alert','title'=>'Delayed order requires review','body'=>"Order #{$order->id} has an open {$alert->type} alert."]); OperationalAlertNotification::query()->create(['operational_alert_id'=>$alert->id,'user_id'=>$userId,'sent_at'=>now()]);}}
    public function delayed(array $filters):array { $q=OperationalAlert::query()->with(['order','order.shipments'])->where('status','!=','resolved'); if(!empty($filters['delay_type']))$q->where('type',$filters['delay_type']);if(!empty($filters['severity']))$q->where('severity',$filters['severity']);if(!empty($filters['status']))$q->where('status',$filters['status']);if(!empty($filters['search']))$q->whereHas('order',fn($x)=>$x->where('id',$filters['search'])->orWhere('status','like','%'.$filters['search'].'%'));if(!empty($filters['provider']))$q->whereHas('order.shipments',fn($x)=>$x->where('provider_code',$filters['provider']));$per=min(100,max(1,(int)($filters['per_page']??25)));$page=max(1,(int)($filters['page']??1));$result=$q->latest('detected_at')->paginate($per,['*'],'page',$page);return ['items'=>$result->items(),'meta'=>['current_page'=>$result->currentPage(),'per_page'=>$result->perPage(),'total'=>$result->total(),'last_page'=>$result->lastPage()]]; }
    public function findAlert(int $id):mixed{return OperationalAlert::query()->with('order')->findOrFail($id);}
    public function acknowledge(int $id,int $userId):mixed{$a=$this->findAlert($id);if($a->status==='open')$a->update(['status'=>'acknowledged','acknowledged_at'=>now(),'acknowledged_by'=>$userId]);return $a->fresh('order');}
    public function resolve(int $id,int $userId):mixed{$a=$this->findAlert($id);$a->update(['status'=>'resolved','resolved_at'=>now(),'resolved_by'=>$userId]);return $a->fresh('order');}
}
