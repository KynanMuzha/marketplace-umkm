<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // ======================
    // LIHAT PROFIL
    // ======================
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            $user->avatar = asset('storage/' . $user->avatar);
        }

        return response()->json($user);
    }

    // ======================
    // UPDATE PROFIL
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

        $user->update($request->only('name', 'email'));

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $user
        ]);
    }

    // ======================
    // UPDATE PASSWORD
    // ======================
    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah'], 400);
        }

        $user->update([
            'password' => bcrypt($request->password)
        ]);

        return response()->json(['message' => 'Password berhasil diubah']);
    }

    // ======================
    // UPLOAD AVATAR
    // ======================
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return response()->json([
            'message' => 'Avatar berhasil diupload',
            'avatar' => asset('storage/' . $path)
        ]);
    }

    // ======================
    // DELETE AVATAR
    // ======================
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = null;
        $user->save();

        return response()->json([
            'message' => 'Avatar berhasil dihapus'
        ]);
    }
}
