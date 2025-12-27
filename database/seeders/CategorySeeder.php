<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        // Daftar kategori yang ingin ditambahkan
        $categories = [
            'Makanan',
            'Minuman',
            'Kerajinan',
            'Pertanian dan Perkebunan',
            'Peternakan dan Perikanan',
            'Produk Herbal'
        ];

        // Loop untuk menambahkan kategori
        foreach ($categories as $cat) {
            // Jika kategori sudah ada, tidak akan ditambahkan lagi
            Category::firstOrCreate(['name' => $cat]);
        }
    }
}
