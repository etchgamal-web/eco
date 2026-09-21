<?php
namespace App\Modules\Monitoring\Presentation\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Monitoring\Application\UseCases\AcknowledgeOperationalAlert;
use App\Modules\Monitoring\Application\UseCases\GetDelayedOrders;
use App\Modules\Monitoring\Application\UseCases\GetMonitoringSettings;
use App\Modules\Monitoring\Application\UseCases\ResolveOperationalAlert;
use App\Modules\Monitoring\Application\UseCases\UpdateMonitoringSetting;
use App\Modules\Monitoring\Presentation\Http\Requests\{MonitoringRequest,AlertActionRequest};
use Illuminate\Http\JsonResponse;
final class MonitoringController extends Controller {
 public function settings(MonitoringRequest $r,GetMonitoringSettings $u):JsonResponse{return response()->json(['data'=>$u->execute()]);}
 public function updateSetting(MonitoringRequest $r,UpdateMonitoringSetting $u):JsonResponse{$d=$r->validated();return response()->json(['data'=>$u->execute((string)$d['rule_type'],(int)$d['days'],(bool)($d['is_enabled']??true))]);}
 public function delayed(MonitoringRequest $r,GetDelayedOrders $u):JsonResponse{return response()->json(['data'=>$u->execute($r->validated())]);}
 public function acknowledge(AlertActionRequest $r,int $id,AcknowledgeOperationalAlert $u):JsonResponse{return response()->json(['data'=>$u->execute($id,(int)$r->user()->id)]);}
 public function resolve(AlertActionRequest $r,int $id,ResolveOperationalAlert $u):JsonResponse{return response()->json(['data'=>$u->execute($id,(int)$r->user()->id)]);}
}
