<?php
namespace App\Modules\Staff\Presentation\Http\Controllers;
use App\Http\Controllers\Controller;use App\Modules\Staff\Domain\ValueObjects\StaffData;use App\Modules\Staff\Application\UseCases\CreateStaff;use App\Modules\Staff\Application\UseCases\DeleteStaff;use App\Modules\Staff\Application\UseCases\GetStaff;use App\Modules\Staff\Application\UseCases\ListStaff;use App\Modules\Staff\Application\UseCases\UpdateStaff;use App\Modules\Staff\Presentation\Http\Requests\StaffRequest;use Illuminate\Http\JsonResponse;
final class StaffController extends Controller
{
 public function index(StaffRequest $request,ListStaff $useCase):JsonResponse{return response()->json(['data'=>$useCase->execute()]);}
 public function store(StaffRequest $request,CreateStaff $useCase):JsonResponse{return response()->json(['data'=>$useCase->execute(StaffData::fromArray($request->validated()),$request->user())],201);}
 public function update(StaffRequest $request,int $id,GetStaff $getStaff,UpdateStaff $useCase):JsonResponse{return response()->json(['data'=>$useCase->execute($getStaff->execute($id),StaffData::fromArray($request->validated()),$request->user())]);}
 public function destroy(StaffRequest $request,int $id,GetStaff $getStaff,DeleteStaff $useCase):JsonResponse{$useCase->execute($getStaff->execute($id),$request->user());return response()->json(['message'=>'Staff deleted successfully.']);}
}
