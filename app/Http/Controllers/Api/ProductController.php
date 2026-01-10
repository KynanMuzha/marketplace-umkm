<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

    class ProductController extends Controller
    {
        public function index()
    {
        return Product::with('category', 'user')
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->get();
    }

    public function sellerIndex(Request $request)
    {
        $perPage = $request->per_page ?? 4;

        return Product::with('category')
            ->where('user_id', Auth::id())
            ->paginate($perPage);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric',
            'stock'       => 'required|numeric',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:2048'
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'user_id' => Auth::id(),
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'description' => $request->description,
            'price'       => $request->price,
            'stock'       => $request->stock,
            'image'       => $imagePath,
        ]);

        return response()->json($product, 201);
    }

    public function show($id)
    {
        return Product::with('category', 'user')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // 🔐 Cek kepemilikan produk
        if ($product->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name'        => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'price'       => 'sometimes|numeric',
            'stock'       => 'sometimes|numeric',
            'description' => 'sometimes|nullable|string',
            'image'       => 'sometimes|image|max:2048'
        ]);

        $product->fill($request->except('image'));

        // Jika upload gambar baru
        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $product->image = $request->file('image')->store('products', 'public');
        }

        $product->save();

        return response()->json([
            'message' => 'Produk berhasil diupdate',
            'data' => $product
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus']);
    }

    public function toggleStatus($id)
    {
        // Cari produk yang dimiliki penjual login
        $product = Product::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->first();

        if (!$product) {
            return response()->json(['message' => 'Produk tidak ditemukan atau bukan milik Anda'], 404);
        }

        // Toggle status
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();

        return response()->json([
            'message' => 'Status produk berhasil diperbarui',
            'status' => $product->status
        ]);
    }

}
