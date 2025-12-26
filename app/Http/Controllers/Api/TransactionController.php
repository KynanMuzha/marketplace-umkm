<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    // 1️⃣ Tambah ke keranjang (pembeli saja)
    public function addToCart(Request $request)
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json([
                'message' => 'Hanya pembeli yang bisa menambahkan produk ke keranjang.'
            ], 403);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1'
        ]);

        $product = Product::find($request->product_id);

        if ($request->quantity > $product->stock) {
            return response()->json([
                'message' => "Stok {$product->name} tidak cukup"
            ], 400);
        }

        $cart = Cart::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $request->product_id],
            ['quantity' => $request->quantity]
        );

        return response()->json([
            'message' => 'Produk berhasil ditambahkan ke keranjang',
            'cart' => $cart
        ]);
    }

    // 2️⃣ Lihat keranjang (pembeli saja)
    public function cart()
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json([
                'message' => 'Hanya pembeli yang bisa melihat keranjang.'
            ], 403);
        }

        $cart = Cart::with('product')
            ->where('user_id', auth()->id())
            ->get();

        return response()->json($cart);
    }

    // 3️⃣ Checkout (pembeli saja)
    public function checkout()
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json([
                'message' => 'Hanya pembeli yang bisa melakukan checkout.'
            ], 403);
        }

        $userId = auth()->id();
        $cartItems = Cart::with('product')->where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Keranjang kosong'], 400);
        }

        foreach ($cartItems as $item) {
            if ($item->quantity > $item->product->stock) {
                return response()->json([
                    'message' => "Stok {$item->product->name} tidak cukup"
                ], 400);
            }
        }

        $total = $cartItems->sum(fn($item) => $item->product->price * $item->quantity);

        $order = Order::create([
            'user_id' => $userId,
            'total'   => $total
        ]);

        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'price'      => $item->product->price
            ]);

            $item->product->decrement('stock', $item->quantity);
        }

        Cart::where('user_id', $userId)->delete();

        return response()->json([
            'message' => 'Checkout berhasil',
            'order'   => $order
        ]);
    }

    // 4️⃣ Riwayat transaksi (pembeli saja)
    public function history()
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json([
                'message' => 'Hanya pembeli yang bisa melihat riwayat transaksi.'
            ], 403);
        }

        $orders = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    // 5️⃣ Update status pesanan (admin/penjual)
    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:menunggu,diproses,dikirim,selesai'
        ]);

        $order = Order::findOrFail($orderId);
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'message' => 'Status pesanan berhasil diperbarui',
            'order' => $order
        ]);
    }
}
