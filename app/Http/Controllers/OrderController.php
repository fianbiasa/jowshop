<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of orders, optionally filtered by status.
     */
    public function index(Request $request): Response
    {
        if ($request->query('status') === 'all') {
            $request->query->remove('status');
        }

        $status = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
        ])['status'] ?? null;

        $orders = Order::query()
            ->with(['customer', 'funnel'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('orders/index', [
            'orders' => $orders,
            'filters' => ['status' => $status],
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): Response
    {
        $order->load(['customer', 'address', 'funnel', 'items.product', 'payments', 'shipment']);

        return Inertia::render('orders/show', [
            'order' => $order,
        ]);
    }
}
