<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $data = User::latest()->paginate(10);

        return response()->json($data);
    }

    public function show(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['data' => User::findOrFail($id)]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'nama'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role'     => 'required|in:administrator,fasilitator',
        ]);

        $user = User::create([
            'nama'      => $request->nama,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => true,
        ]);

        ActivityLog::log('create_user', 'User ' . $user->nama . ' ditambahkan.', 'User', $user->id);

        return response()->json(['message' => 'User berhasil ditambahkan.', 'data' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $user = User::findOrFail($id);

        $request->validate([
            'nama'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role'     => 'required|in:administrator,fasilitator',
        ]);

        $user->update([
            'nama'     => $request->nama,
            'email'    => $request->email,
            'password' => $request->filled('password') ? Hash::make($request->password) : $user->password,
            'role'     => $request->role,
        ]);

        ActivityLog::log('update_user', 'User ' . $user->nama . ' diperbarui.', 'User', $id);

        return response()->json(['message' => 'User berhasil diperbarui.', 'data' => $user]);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($request->user()->id === (int) $id) {
            return response()->json(['message' => 'Tidak bisa menghapus akun sendiri.'], 422);
        }

        $user = User::findOrFail($id);
        $user->delete();

        ActivityLog::log('delete_user', 'User ' . $user->nama . ' dihapus.', 'User', $id);

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    public function toggleActive(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::log('toggle_user', 'User ' . $user->nama . ' ' . $status . '.', 'User', $id);

        return response()->json(['message' => 'Status user berhasil diubah.', 'data' => $user]);
    }
}
