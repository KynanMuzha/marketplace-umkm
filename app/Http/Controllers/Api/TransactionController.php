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

        $cart = Cart::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($cart) {
            // Produk sudah ada, tambah quantity
            $newQty = $cart->quantity + $request->quantity;

            if ($newQty > $product->stock) {
                return response()->json(['message' => "Stok {$product->name} tidak cukup"], 400);
            }

            $cart->quantity = $newQty;
            $cart->save();
        } else {
            // Produk belum ada, buat baru
            if ($request->quantity > $product->stock) {
                return response()->json(['message' => "Stok {$product->name} tidak cukup"], 400);
            }

            $cart = Cart::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        return response()->json([
            'message' => 'Produk berhasil ditambahkan ke keranjang',
            'cart' => $cart
        ]);
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

    // 3️⃣b Hapus produk dari keranjang (pembeli saja)
    public function removeFromCart($id)
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json([
                'message' => 'Hanya pembeli yang bisa menghapus keranjang.'
            ], 403);
        }

        $cart = Cart::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Produk tidak ditemukan di keranjang'
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus dari keranjang'
        ]);
    }

    // 4️⃣ Checkout (pembeli saja)
    public function checkout(Request $request)
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json(['message' => 'Hanya pembeli yang bisa melakukan checkout.'], 403);
        }

        $request->validate([
            'delivery_type'  => 'required|in:DELIVERY,PICKUP',
            'shipping_cost'  => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:QRIS,TF_BANK,E_WALLET,COD',
            'payment_detail' => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        $userId = auth()->id();

        if ($request->delivery_type === 'PICKUP') {
            $shippingMethod = 'AMBIL_DI_TOKO';
            $shippingCost = 0;
        } else {
            $shippingMethod = 'DIANTAR_TOKO';
            $shippingCost = $request->shipping_cost ?? 0;
        }

        // Ambil item yang dikirim dari request
        $cartItems = Cart::with('product')
            ->where('user_id', $userId)
            ->whereIn('product_id', collect($request->items)->pluck('product_id'))
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Keranjang kosong atau item tidak valid'], 400);
        }

        // cek stok
        foreach ($cartItems as $item) {
            $reqQty = collect($request->items)
                ->firstWhere('product_id', $item->product_id)['quantity'] ?? 0;
            if ($reqQty > $item->product->stock) {
                return response()->json(['message' => "Stok {$item->product->name} tidak cukup"], 400);
            }
        }

        $subtotal = 0;
        foreach ($cartItems as $item) {
            $reqQty = collect($request->items)
                ->firstWhere('product_id', $item->product_id)['quantity'];
            $subtotal += $item->product->price * $reqQty;
        }

        $total = $subtotal + $shippingCost;

        // saat buat order
        $order = Order::create([
            'user_id'         => $userId,
            'total'           => $total,
            'shipping_method' => $shippingMethod,
            'shipping_cost'   => $shippingCost,
            'payment_method'  => $request->payment_method,
            'payment_detail'  => $request->payment_detail,
            'payment_status'  => 'pending',
            'payment_code'    => rand(100000, 999999), // kode pembayaran 6 digit
            'payment_due'     => now()->addHours(24),  // tenggat bayar 24 jam
        ]);

        // buat order item & kurangi stok
        foreach ($cartItems as $item) {
            $reqQty = collect($request->items)
                ->firstWhere('product_id', $item->product_id)['quantity'];

            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item->product_id,
                'quantity'   => $reqQty,
                'price'      => $item->product->price
            ]);

            $item->product->decrement('stock', $reqQty);

            // hapus dari cart
            $item->delete();
        }

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
