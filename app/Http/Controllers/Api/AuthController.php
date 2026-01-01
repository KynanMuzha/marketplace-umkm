<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REGISTER
    |--------------------------------------------------------------------------
    */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,penjual,pembeli',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role'     => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user'    => $user,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user'    => $user, // avatar BIAR DIHANDLE ProfileController
            'token'   => $token,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FORGOT PASSWORD (KIRIM OTP)
    |--------------------------------------------------------------------------
    */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email tidak ditemukan',
            ], 404);
        }

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp'             => $otp,
            'otp_expires_at'  => now()->addMinutes(5),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json([
            'message' => 'OTP berhasil dikirim',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD
    |--------------------------------------------------------------------------
    */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'otp'                   => 'required|string',
            'password'              => 'required|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        if ($user->otp !== $request->otp) {
            return response()->json(['message' => 'OTP tidak valid'], 400);
        }

        if ($user->otp_expires_at && now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP sudah kadaluarsa'], 400);
        }

        $user->update([
            'password'        => bcrypt($request->password),
            'otp'             => null,
            'otp_expires_at'  => null,
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah',
        ]);
    }
}
