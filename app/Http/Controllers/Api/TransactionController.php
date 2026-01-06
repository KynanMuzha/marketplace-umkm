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
            return response()->json(['message' => 'Hanya pembeli yang bisa menambahkan produk ke keranjang.'], 403);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1'
        ]);

        $product = Product::find($request->product_id);

        if ($request->quantity > $product->stock) {
            return response()->json(['message' => "Stok {$product->name} tidak cukup"], 400);
        }

        $cart = Cart::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $request->product_id],
            ['quantity' => $request->quantity]
        );

        return response()->json(['message' => 'Produk berhasil ditambahkan ke keranjang', 'cart' => $cart]);
    }

    // 2️⃣ Lihat keranjang (pembeli saja)
    public function cart()
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json(['message' => 'Hanya pembeli yang bisa melihat keranjang.'], 403);
        }

        $cart = Cart::with([
            'product.user',      // 🔥 UNTUK NAMA TOKO
            'product.category'
        ])
        ->where('user_id', auth()->id())
        ->get();

        return response()->json($cart);
    }

    // 3️⃣ Update jumlah produk di keranjang (pembeli saja)
    public function updateCartQuantity(Request $request)
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json(['message' => 'Hanya pembeli yang bisa mengubah keranjang.'], 403);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1'
        ]);

        $cart = Cart::where('user_id', auth()->id())
                    ->where('product_id', $request->product_id)
                    ->first();

        if (!$cart) {
            return response()->json(['message' => 'Produk tidak ada di keranjang'], 404);
        }

        $product = $cart->product;

        if ($request->quantity > $product->stock) {
            return response()->json(['message' => "Stok {$product->name} tidak cukup"], 400);
        }

        $cart->quantity = $request->quantity;
        $cart->save();

        return response()->json(['message' => 'Jumlah produk di keranjang berhasil diperbarui', 'cart' => $cart]);
    }

    // 4️⃣ Checkout (pembeli saja)
    public function checkout(Request $request)
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json(['message' => 'Hanya pembeli yang bisa melakukan checkout.'], 403);
        }

        $request->validate([
            'shipping_method' => 'required|string',
            'shipping_cost'   => 'required|numeric|min:0',
            'payment_method'  => 'required|string'
        ]);

        $userId = auth()->id();
        $cartItems = Cart::with('product')->where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Keranjang kosong'], 400);
        }

        foreach ($cartItems as $item) {
            if ($item->quantity > $item->product->stock) {
                return response()->json(['message' => "Stok {$item->product->name} tidak cukup"], 400);
            }
        }

        $subtotal = $cartItems->sum(fn($item) => $item->product->price * $item->quantity);
        $total = $subtotal + $request->shipping_cost;

        $order = Order::create([
            'user_id'         => $userId,
            'total'           => $total,
            'shipping_method' => $request->shipping_method,
            'shipping_cost'   => $request->shipping_cost,
            'payment_method'  => $request->payment_method,
            'payment_status'  => 'pending',
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

        return response()->json(['message' => 'Checkout berhasil', 'order' => $order]);
    }

    // 5️⃣ Riwayat transaksi (pembeli saja)
    public function history()
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json(['message' => 'Hanya pembeli yang bisa melihat riwayat transaksi.'], 403);
        }

        $orders = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    // 6️⃣ Update status pesanan (admin/penjual)
    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:menunggu,diproses,dikirim,selesai'
        ]);

        $order = Order::findOrFail($orderId);
        $order->status = $request->status;
        $order->save();

        return response()->json(['message' => 'Status pesanan berhasil diperbarui', 'order' => $order]);
    }
}
