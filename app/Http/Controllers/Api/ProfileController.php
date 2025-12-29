<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // ======================
    // 1️⃣ LIHAT PROFIL
    // ======================
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    // ======================
    // 2️⃣ UPDATE PROFIL
    // ======================
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'  => 'required|string',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ]
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $user
        ]);
    }

    // ======================
    // 3️⃣ UPDATE PASSWORD
    // ======================
    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'message' => 'Password lama salah'
            ], 400);
        }

        $user->update([
            'password' => bcrypt($request->password)
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah'
        ]);
    }

    // ======================
    // 4️⃣ UPLOAD FOTO PROFIL
    // ======================
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $user = $request->user();

        // Hapus foto lama
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update([
            'avatar' => $path
        ]);

        return response()->json([
            'message' => 'Foto profil berhasil diperbarui',
            'avatar' => asset('storage/' . $path)
        ]);
    }

    // ======================
    // 5️⃣ HAPUS FOTO PROFIL
    // ======================
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update([
            'avatar' => null
        ]);

        return response()->json([
            'message' => 'Foto profil berhasil dihapus'
        ]);
    }
}