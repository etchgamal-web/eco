<?php
namespace App\Modules\Monitoring\Infrastructure\Models;
use App\Modules\Order\Infrastructure\Models\CustomerOrder;
use App\Modules\Auth\Infrastructure\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OperationalAlert extends Model { protected $fillable=['order_id','type','severity','status','detected_at','acknowledged_at','resolved_at','acknowledged_by','resolved_by','metadata']; protected function casts():array{return ['detected_at'=>'datetime','acknowledged_at'=>'datetime','resolved_at'=>'datetime','metadata'=>'array'];} public function order():BelongsTo{return $this->belongsTo(CustomerOrder::class,'order_id');} public function acknowledgedBy():BelongsTo{return $this->belongsTo(User::class,'acknowledged_by');} public function resolvedBy():BelongsTo{return $this->belongsTo(User::class,'resolved_by');} }
