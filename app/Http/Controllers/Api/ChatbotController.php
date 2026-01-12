<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class ChatbotController extends Controller
{
  public function ask(Request $request)
  {
    $client = new Client();

    $res = $client->post('https://api.openai.com/v1/chat/completions', [
      'headers' => [
        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        'Content-Type'  => 'application/json',
      ],
      'json' => [
        'model' => 'gpt-4.1-mini',
        'messages' => [
          ['role'=>'system','content'=>'Kamu adalah CS marketplace UMKM Desa yang ramah, profesional, dan singkat.'],
          ['role'=>'user','content'=>$request->message]
        ],
        'temperature' => 0.6
      ]
    ]);

    $data = json_decode($res->getBody(), true);
    return response()->json([
      'reply' => $data['choices'][0]['message']['content']
    ]);
  }
}
