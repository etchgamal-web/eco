<?php
namespace App\Modules\Monitoring\Presentation\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Modules\Monitoring\Application\UseCases\{AcknowledgeOperationalAlert,DetectDelayedOrders,GetDelayedOrders,GetMonitoringSettings,GetOperationalAlert,GetOperationalAlerts,ResolveOperationalAlert,UpdateMonitoringSetting};
use App\Modules\Monitoring\Presentation\Http\Requests\{AlertActionRequest,MonitoringRequest,RunMonitoringRequest};
use Illuminate\Http\JsonResponse;
final class MonitoringController extends Controller {
 public function settings(MonitoringRequest $r,GetMonitoringSettings $u):JsonResponse{return response()->json(['data'=>$u->execute()]);}
 public function updateSetting(MonitoringRequest $r,UpdateMonitoringSetting $u):JsonResponse{$d=$r->validated();return response()->json(['data'=>$u->execute((string)$d['rule_type'],(int)$d['days'],(bool)($d['is_enabled']??true))]);}
 public function delayed(MonitoringRequest $r,GetDelayedOrders $u):JsonResponse{return response()->json(['data'=>$u->execute($r->validated())]);}
 public function run(RunMonitoringRequest $r,DetectDelayedOrders $u):JsonResponse{return response()->json(['data'=>$u->execute()]);}
 public function alerts(MonitoringRequest $r,GetOperationalAlerts $u):JsonResponse{return response()->json(['data'=>$u->execute($r->validated())]);}
 public function alert(MonitoringRequest $r,int $id,GetOperationalAlert $u):JsonResponse{return response()->json(['data'=>$u->execute($id)]);}
 public function acknowledge(AlertActionRequest $r,int $id,AcknowledgeOperationalAlert $u):JsonResponse{return response()->json(['data'=>$u->execute($id,(int)$r->user()->id)]);}
 public function resolve(AlertActionRequest $r,int $id,ResolveOperationalAlert $u):JsonResponse{return response()->json(['data'=>$u->execute($id,(int)$r->user()->id)]);}
}
