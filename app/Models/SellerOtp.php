<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerOtp extends Model
{
    protected $fillable = [
        'email',
        'otp',
        'expired_at',
    ];

    public $timestamps = true;
}
