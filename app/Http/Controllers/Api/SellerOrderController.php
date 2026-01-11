<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Milon\Barcode\Facades\DNS1DFacade as DNS1D;


class SellerOrderController extends Controller
{
    /**
     * LIST PESANAN MASUK (SELLER)
     */
    public function index()
    {
        $sellerId = Auth::id();

        $orders = Order::whereHas('items.product', function ($query) use ($sellerId) {
                $query->where('user_id', $sellerId);
            })
            ->with(['items.product', 'user'])
            ->latest()
            ->get();

        return response()->json($orders);
    }

    /**
     * DETAIL PESANAN
     */
    public function show($id)
{
    $sellerId = Auth::id();

    $order = Order::with(['items.product:id,name'])
        ->where('id', $id)
        ->whereHas('items.product', function ($q) use ($sellerId) {
            $q->where('user_id', $sellerId);
        })
        ->firstOrFail();

    return response()->json([
        'id' => $order->id,

        // 🔥 PAKAI DATA ORDER (BUKAN USER)
        'customer_name' => $order->customer_name,

        // ⬇️ INI KUNCI NYA
        'customer_address' =>
            $order->customer_address
            ?? $order->address
            ?? '-',

        'customer_phone' => $order->customer_phone ?? '-',

        'total' => $order->total,
        'status' => $order->status,
        'created_at' => $order->created_at,

        'items' => $order->items->map(function ($item) {
            return [
                'product_name' => $item->product?->name ?? '-',
                'quantity' => $item->quantity,
                'price' => $item->price
            ];
        })
    ]);
}
    /**
     * UPDATE STATUS PESANAN
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:processing,shipped,completed,cancelled'
        ]);

        $sellerId = Auth::id();

        $order = Order::where('id', $id)
            ->whereHas('items.product', function ($query) use ($sellerId) {
                $query->where('user_id', $sellerId);
            })
            ->firstOrFail();

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'message' => 'Status pesanan berhasil diperbarui',
            'order'   => $order
        ]);
    }

    public function downloadShippingLabel($id)
{
    $sellerId = Auth::id();

    $order = Order::with(['items.product.user'])
        ->where('id', $id)
        ->whereHas('items.product', function ($q) use ($sellerId) {
            $q->where('user_id', $sellerId);
        })
        ->firstOrFail();

    $trackingNumber = 'UMKM-' . strtoupper(uniqid());

    $barcode = DNS1D::getBarcodePNG($trackingNumber, 'C128', 1.2, 45);

    $pdf = Pdf::loadView('pdf.shipping-label', [
        'order' => $order,
        'trackingNumber' => $trackingNumber,
        'barcode' => $barcode,
    ])->setPaper([0, 0, 226.77, 567]);

    return $pdf->download('shipping-label-'.$order->id.'.pdf');
}

}