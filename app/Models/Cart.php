<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id', 'product_id', 'quantity'];

    public function product() {
        // withDefault() mencegah error kalau product sudah dihapus
        return $this->belongsTo(Product::class)->withDefault();
    }

    public function user() {
        // withDefault() mencegah error kalau user sudah dihapus
        return $this->belongsTo(User::class)->withDefault();
    }
}
