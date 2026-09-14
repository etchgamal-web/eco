<?php

namespace App\Modules\Customer\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Domain\ValueObjects\UpdateCustomerData;
use App\Modules\Customer\Application\UseCases\GetCustomerProfile;
use App\Modules\Customer\Application\UseCases\UpdateCustomerProfile;
use App\Modules\Customer\Presentation\Http\Requests\UpdateCustomerProfileRequest;
use App\Modules\Customer\Presentation\Http\Requests\ViewCustomerProfileRequest;
use Illuminate\Http\JsonResponse;

final class CustomerController extends Controller
{
    public function profile(ViewCustomerProfileRequest $request, GetCustomerProfile $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->execute()]);
    }

    public function updateProfile(UpdateCustomerProfileRequest $request, UpdateCustomerProfile $useCase): JsonResponse
    {
        return response()->json([
            'data' => $useCase->execute(UpdateCustomerData::fromArray($request->validated())),
        ]);
    }
}
