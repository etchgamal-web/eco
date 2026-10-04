<?php

namespace App\Modules\Reporting\Infrastructure\Persistence;

use App\Modules\Reporting\Domain\Contracts\SalesAnalyticsReaderInterface;
use Illuminate\Support\Facades\DB;

final class EloquentSalesAnalyticsReader implements SalesAnalyticsReaderInterface
{
    private const INCLUDED_STATUSES = ['pending', 'reviewing', 'confirmed', 'processing', 'shipped', 'delivered'];

    public function read(string $from, string $to): array
    {
        $orders = DB::table('customer_orders')->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->whereIn('status', self::INCLUDED_STATUSES);
        $gross = (int) (clone $orders)->sum('total_amount');
        $orderCount = (int) (clone $orders)->count();
        $refunds = (int) DB::table('order_returns')->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->whereIn('status', ['accepted', 'completed', 'refunded'])->sum(DB::raw('coalesce(actual_customer_refund, refund_amount, 0)'));

        return [
            'summary' => ['sales' => $gross, 'orders' => $orderCount, 'average_order' => $orderCount > 0 ? (int) round($gross / $orderCount) : 0, 'refunds' => $refunds, 'net_sales' => $gross - $refunds, 'currency' => (string) ((clone $orders)->value('currency') ?? 'EGP')],
            'daily' => (clone $orders)->selectRaw('date(created_at) as date, count(*) as orders, sum(total_amount) as sales')->groupByRaw('date(created_at)')->orderBy('date')->get()->map(fn ($row) => ['date' => $row->date, 'orders' => (int) $row->orders, 'sales' => (int) $row->sales])->all(),
            'products' => DB::table('customer_order_items')->join('customer_orders', 'customer_orders.id', '=', 'customer_order_items.order_id')->whereBetween('customer_orders.created_at', [$from.' 00:00:00', $to.' 23:59:59'])->whereIn('customer_orders.status', self::INCLUDED_STATUSES)->selectRaw('customer_order_items.product_id as product_id, customer_order_items.name as name, sum(customer_order_items.quantity) as quantity, sum(customer_order_items.total_amount) as sales')->groupBy('customer_order_items.product_id', 'customer_order_items.name')->orderByDesc('sales')->limit(10)->get()->map(fn ($row) => ['product_id' => $row->product_id ? (int) $row->product_id : null, 'name' => $row->name, 'quantity' => (int) $row->quantity, 'sales' => (int) $row->sales])->all(),
            'customers' => DB::table('customer_orders')->leftJoin('users', 'users.id', '=', 'customer_orders.user_id')->whereBetween('customer_orders.created_at', [$from.' 00:00:00', $to.' 23:59:59'])->whereIn('customer_orders.status', self::INCLUDED_STATUSES)->selectRaw('customer_orders.user_id as customer_id, coalesce(users.name, users.email, customer_orders.guest_email, \'زائر\') as name, count(*) as orders, sum(customer_orders.total_amount) as sales')->groupBy('customer_orders.user_id', 'users.name', 'users.email', 'customer_orders.guest_email')->orderByDesc('sales')->limit(10)->get()->map(fn ($row) => ['customer_id' => $row->customer_id ? (int) $row->customer_id : null, 'name' => $row->name, 'orders' => (int) $row->orders, 'sales' => (int) $row->sales])->all(),
        ];
    }
}
