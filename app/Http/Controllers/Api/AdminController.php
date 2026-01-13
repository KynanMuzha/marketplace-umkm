<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // 🔹 Kelola user
    public function listUsers()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function showUser(User $user)
    {
        return response()->json($user);
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:admin,penjual,pembeli'
        ]);

        $user->update($request->only(['name','email','role']));
        return response()->json(['message' => 'User berhasil diperbarui', 'user' => $user]);
    }

    public function deleteUser(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User berhasil dihapus']);
    }

    // 🔹 Monitoring transaksi
    public function allOrders()
    {
        $orders = Order::with('items.product','user')->orderBy('created_at','desc')->get();
        return response()->json($orders);
    }

    public function showOrder(Order $order)
    {
        $order->load('items.product','user');
        return response()->json($order);
    }

    // 🔹 Laporan penjualan
    public function salesReport(Request $request)
    {
        $query = OrderItem::select(
            'product_id',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(price * quantity) as total_sales')
        )->groupBy('product_id');

        if ($request->start_date && $request->end_date) {
            $query->whereHas('order', function($q) use ($request) {
                $q->whereBetween('created_at', [$request->start_date, $request->end_date]);
            });
        }

        $report = $query->with('product')->get();

        return response()->json($report);
    }

    // 🔹 Update status pesanan
    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,completed,cancelled'
        ]);

        $order->update([
            'status' => $request->status
        ]);

        return response()->json([   
            'message' => 'Status pesanan berhasil diperbarui',
            'order' => $order
        ]);
    }
}
