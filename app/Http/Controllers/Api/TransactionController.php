<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


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
            'delivery_type'     => 'required|in:DELIVERY,PICKUP',
            'shipping_cost'     => 'nullable|numeric|min:0',
            'payment_method'    => 'required|in:QRIS,TF_BANK,E_WALLET,COD',
            'payment_detail'    => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id'=> 'required|exists:products,id',
            'items.*.quantity'  => 'required|integer|min:1',
        ]);
        DB::beginTransaction();
        try {

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
        // ✅ VALIDASI TAMBAHAN (WAJIB)
        if ($cartItems->count() !== count($request->items)) {
            return response()->json([
                'message' => 'Item checkout tidak sesuai dengan keranjang'
            ], 400);
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

        // ================== PAYMENT ==================
        $paymentCode = null;
        $paymentDue  = null;
        $paymentUrl  = null;

        switch ($request->payment_method) {
            case 'QRIS':
                $paymentCode = 'QR-' . strtoupper(uniqid());
                $paymentDue  = now()->addMinutes(15);
                $paymentUrl = $paymentCode;
                break;

            case 'TF_BANK':
                $paymentCode = 'VA-' . rand(1000000000, 9999999999);
                $paymentDue  = now()->addHours(24);
                $paymentUrl  = null; // PENTING
                break;

            case 'E_WALLET':
                $paymentCode = 'EW-' . strtoupper(uniqid());
                $paymentDue  = now()->addMinutes(30);
                $paymentUrl  = null; // PENTING
                break;

            case 'COD':
                $paymentCode = null;
                $paymentDue  = null;
                $paymentUrl  = null;
                break;
        }
       if ($request->payment_method === 'COD') {
            $paymentStatus = 'unpaid';
            $status = 'processing';   // 🔥 COD langsung diproses
        } else {
            $paymentStatus = 'pending';
            $status = 'pending';      // 🔥 selain COD menunggu pembayaran
        }

        $paymentDetail = $request->payment_method === 'COD'
    ? 'COD'
    : $request->payment_detail;

    $user = auth()->user();
        $order = Order::create([
        'user_id' => $userId,
        'customer_name' => $request->customer_name ?? null,
        'customer_address' => $request->customer_address ?? null,
        'customer_phone' => $request->customer_phone ?? null,
        'total' => $total,
        'status' => $status, 
        'shipping_method' => $shippingMethod,
        'shipping_cost' => $shippingCost,
        'payment_method' => $request->payment_method,
        'payment_detail' => $paymentDetail ?? null,
        'payment_status' => $paymentStatus, 
        'payment_code' => $paymentCode,
        'payment_url' => $paymentUrl,
        'payment_due' => $paymentDue,
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
        DB::commit(); // ✅ SIMPAN PERMANEN

        return response()->json([
            'message' => 'Checkout berhasil',
            'order' => $order->load('items.product')
        ]);
    } 

    catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Checkout error: '.$e->getMessage(), [
            'user_id' => auth()->id(),
            'payload' => $request->all()
        ]);
        return response()->json([
            'message' => 'Checkout gagal',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // 5️⃣ Riwayat transaksi (pembeli saja)
    public function history(Request $request)
    {
        if (auth()->user()->role !== 'pembeli') {
            return response()->json(['message' => 'Hanya pembeli yang bisa melihat riwayat transaksi.'], 403);
        }

        $status = $request->query('status');

        $orders = Order::with(['items.product', 'items.product.user'])
            ->where('user_id', auth()->id())
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $orders->map(function ($order) {

                $firstItem = $order->items->first();

                return [
                    'id' => $order->id,
                    'invoice' => 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),

                    // 🔥 AMAN JIKA ITEMS KOSONG
                    'product' => $firstItem?->product?->name ?? '-',
                    'seller' => $firstItem?->product?->user?->name ?? '-',

                    'price' => $order->total,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'payment_code' => $order->payment_code,
                    'payment_due' => $order->payment_due,

                    'status' => [
                        'code' => $order->status,
                        'label' => match($order->status) {
                            'pending' => 'Menunggu Pembayaran',
                            'processing' => 'Diproses',
                            'shipped' => 'Dikirim',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                            default => '-'
                        }
                    ],

                    'created_at' => $order->created_at
                ];
            })
        ]);
    }


    // 6️⃣ Update status pesanan (admin/penjual)
    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,completed,cancelled'
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
