<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    
    public function index(Request $request)
{
    try {
        $query = Product::query();

        // pastikan kolom ada sebelum dipakai
        if (\Schema::hasColumn('products', 'status')) {
            $query->where('status', 'active');
        }

        if (\Schema::hasColumn('products', 'stock')) {
            $query->whereNotNull('stock')->where('stock', '>', 0);
        }

        // relasi hanya jika method ada
        if (method_exists(Product::class, 'category')) {
            $query->with('category');
        }

        if (method_exists(Product::class, 'user')) {
            $query->with('user');
        }

        // filter search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // filter berdasarkan kategori
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return response()->json($query->get());
    }
    catch (\Throwable $e) {
        \Log::error('PRODUCT ERROR: ' . $e->getMessage());
        return response()->json(['message' => 'Server error'], 500);
    }
}


    /**
     * 🔐 LIST PRODUK SELLER (PAGINATION)
     */
    public function sellerIndex(Request $request)
{
    $perPage = $request->per_page ?? 4;

    $query = Product::with('category')
        ->where('user_id', Auth::id());

    // 🔍 SEARCH PRODUK SELLER
    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    return $query->paginate($perPage);
}


    /**
     * ➕ TAMBAH PRODUK
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric',
            'stock'       => 'required|numeric',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'user_id'     => Auth::id(),
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'description' => $request->description,
            'price'       => $request->price,
            'stock'       => $request->stock,
            'image'       => $imagePath,
            'status'      => 'active',
        ]);

        return response()->json($product, 201);
    }

    /**
     * 🔍 DETAIL PRODUK
     */
    public function show($id)
    {
        return Product::with('category', 'user')->findOrFail($id);
    }

    /**
     * ✏️ UPDATE PRODUK
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($product->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name'        => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'price'       => 'sometimes|numeric',
            'stock'       => 'sometimes|numeric',
            'description' => 'sometimes|nullable|string',
            'image'       => 'sometimes|image|max:2048',
        ]);

        $product->fill($request->except('image'));

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $product->image = $request->file('image')->store('products', 'public');
        }

        $product->save();

        return response()->json([
            'message' => 'Produk berhasil diupdate',
            'data' => $product,
        ]);
    }

    /**
     * 🗑 HAPUS PRODUK
     */
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

    /**
     * 🔄 TOGGLE STATUS PRODUK
     */
    public function toggleStatus($id)
    {
        $product = Product::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Produk tidak ditemukan atau bukan milik Anda',
            ], 404);
        }

        $product->status = $product->status === 'active'
            ? 'inactive'
            : 'active';

        $product->save();

        return response()->json([
            'message' => 'Status produk berhasil diperbarui',
            'status'  => $product->status,
        ]);
    }
}
