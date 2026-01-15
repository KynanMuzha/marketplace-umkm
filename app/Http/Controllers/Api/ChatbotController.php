<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function ask(Request $request)
    {
        return response()->json([
            'reply' => 'Kami adalah CS marketplace UMKM Desa yang ramah, profesional, dan singkat'
        ]);
    }
}
