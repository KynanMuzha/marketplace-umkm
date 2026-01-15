<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\OrderItem;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_address',
        'customer_phone',
        'total',
        'shipping_method',
        'shipping_cost',
        'payment_method',
        'payment_detail',
        'payment_status',
        'payment_code',
        'payment_url',
        'payment_due',
        'payment_proof',  
        'payment_verified_at',
        'payment_rejected_reason',
        'status',
    ];

    // 🔥 PENTING UNTUK EXPIRED TIME
    protected $casts = [
        'payment_due' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
