<?php

namespace App\Modules\Customer\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Application\UseCases\GetAdminCustomers;
use App\Modules\Customer\Application\UseCases\ManageAdminCustomer;
use App\Modules\Customer\Presentation\Http\Requests\CustomerAdminRequest;
use Illuminate\Http\JsonResponse;

final class CustomerAdminController extends Controller
{
    public function index(CustomerAdminRequest $request, GetAdminCustomers $customers): JsonResponse
    {
        $result = $customers->index(trim((string) $request->validated('search', '')), (int) $request->validated('per_page', 20));
        return response()->json($result);
    }

    public function export(CustomerAdminRequest $request, GetAdminCustomers $customers)
    {
        $rows = $customers->export(trim((string) $request->validated('search', '')));
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb'); fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Status', 'Orders', 'Total spent', 'Created at']);
            foreach ($rows as $row) fputcsv($handle, [$row['id'], $row['name'], $row['email'], $row['phone'], $row['status'], $row['orders_count'], $row['total_spent'], $row['created_at']]);
            fclose($handle);
        }, 'customers.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(CustomerAdminRequest $request, int $customerId, GetAdminCustomers $customers): JsonResponse
    {
        return response()->json(['data' => $customers->show($customerId)]);
    }

    public function update(CustomerAdminRequest $request, int $customerId, ManageAdminCustomer $manager): JsonResponse
    {
        return response()->json(['data' => $manager->update($customerId, $request->validated(), $request->user())]);
    }

    public function storeAddress(CustomerAdminRequest $request, int $customerId, ManageAdminCustomer $manager): JsonResponse
    {
        return response()->json(['data' => $manager->createAddress($customerId, $request->validated(), $request->user())], 201);
    }

    public function updateAddress(CustomerAdminRequest $request, int $customerId, int $addressId, ManageAdminCustomer $manager): JsonResponse
    {
        return response()->json(['data' => $manager->updateAddress($customerId, $addressId, $request->validated(), $request->user())]);
    }

    public function destroyAddress(CustomerAdminRequest $request, int $customerId, int $addressId, ManageAdminCustomer $manager): JsonResponse
    {
        $manager->deleteAddress($customerId, $addressId, $request->user());
        return response()->json(['message' => 'Address deleted.']);
    }
}
