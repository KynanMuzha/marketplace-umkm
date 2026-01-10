<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;

class CartController extends Controller
{
    // Ambil semua item di cart user
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $cart = Cart::with('product')->where('user_id', $user->id)->get();

            return response()->json($cart);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Tambah produk ke cart
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:1'
        ]);

        $cartItem = Cart::updateOrCreate(
            ['user_id' => $user->id, 'product_id' => $request->product_id],
            ['quantity' => $request->quantity]
        );

        return response()->json($cartItem, 201);
    }

    // Hapus produk dari cart
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $cartItem = Cart::where('user_id', $user->id)->where('id', $id)->first();
        if (!$cartItem) return response()->json(['message' => 'Not found'], 404);

        $cartItem->delete();
        return response()->json(['message' => 'Item removed']);
    }
}
