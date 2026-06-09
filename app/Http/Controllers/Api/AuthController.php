<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Akun kamu tidak aktif. Hubungi administrator.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLog::log('login', 'User ' . $user->nama . ' berhasil login.');

        return response()->json([
            'message' => 'Login berhasil.',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        ActivityLog::log('logout', 'User ' . $request->user()->nama . ' logout.');

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => [
                'id'    => $request->user()->id,
                'nama'  => $request->user()->nama,
                'email' => $request->user()->email,
                'role'  => $request->user()->role,
            ],
        ]);
    }
    
    // --- TAMBAHAN BARU: Fungsi untuk Update Profil ---
    public function updateProfile(Request $request)
    {
        $request->validate([
            'nama'  => 'required|string|max:255',
            // Pastikan email unik, kecuali untuk ID user yang sedang login
            'email' => 'required|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->nama = $request->nama;
        $user->email = $request->email;
        $user->save();

        ActivityLog::log('update_profile', 'User ' . $user->nama . ' memperbarui data profil.');

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user'    => [
                'id'    => $user->id,
                'nama'  => $user->nama,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    // --- TAMBAHAN BARU: Fungsi untuk Update Password ---
    public function updatePassword(Request $request)
    {
        $request->validate([
            // confirmed akan otomatis mencocokkan dengan field password_confirmation dari Vue
            'password' => 'required|string|min:8|confirmed', 
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->save();

        ActivityLog::log('update_password', 'User ' . $user->nama . ' mengubah password akun.');

        return response()->json([
            'message' => 'Password berhasil diperbarui.',
        ]);
    }
}