<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SellerOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class SellerAuthController extends Controller
{
    /* ================= REGISTER SELLER ================= */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        // SIMPAN USER (BELUM AKTIF SEBAGAI SELLER)
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'penjual',
        ]);

        // GENERATE OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        SellerOtp::updateOrCreate(
            ['email' => $validated['email']],
            [
                'otp' => $otp,
                'expired_at' => now()->addMinutes(5),
            ]
        );

        // KIRIM EMAIL
        Mail::to($validated['email'])->send(new OtpMail($otp));

        return response()->json([
            'message' => 'Registrasi penjual berhasil, OTP dikirim ke email',
        ], 201);
    }

    /* ================= VERIFY OTP ================= */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string',
        ]);

        $record = SellerOtp::where('email', $request->email)->first();

        if (!$record) {
            return response()->json(['message' => 'OTP tidak ditemukan'], 404);
        }

        if ($record->otp !== $request->otp) {
            return response()->json(['message' => 'OTP tidak valid'], 400);
        }

        if (now()->greaterThan($record->expired_at)) {
            return response()->json(['message' => 'OTP sudah kedaluwarsa'], 400);
        }

        // OTP VALID → HAPUS
        $record->delete();

        return response()->json([
            'message' => 'Verifikasi OTP berhasil',
        ]);
    }
}
