<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // PUBLIC: ambil semua category
    public function index()
    {
        return Category::select('id', 'name')->get();
    }

    public function products($id)
    {
        $category = Category::with(['products' => function ($q) {
            $q->where('status', 'active')
            ->where('stock', '>', 0);
        }])->findOrFail($id);

        return response()->json($category->products);
    }

    // ADMIN: tambah category
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name'
        ]);

        $category = Category::create([
            'name' => $request->name
        ]);

        return response()->json($category, 201);
    }

    // ADMIN: update category
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name,' . $category->id
        ]);

        $category->update([
            'name' => $request->name
        ]);

        return response()->json($category);
    }

    // ADMIN: hapus category
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }

    // PUBLIC: ambil 1 kategori (nama kategori)
public function show($id)
{
    return Category::select('id', 'name')->findOrFail($id);
}

}
