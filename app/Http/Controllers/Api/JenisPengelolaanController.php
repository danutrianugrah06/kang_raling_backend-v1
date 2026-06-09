<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\JenisPengelolaan;
use Illuminate\Http\Request;

class JenisPengelolaanController extends Controller
{
    public function index()
    {
        return response()->json(['data' => JenisPengelolaan::all()]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'nama'      => 'required|string|max:255|unique:jenis_pengelolaans,nama',
            'deskripsi' => 'nullable|string',
        ]);

        $data = JenisPengelolaan::create($request->only('nama', 'deskripsi'));

        ActivityLog::log('create_jenis_pengelolaan', 'Jenis pengelolaan ' . $data->nama . ' ditambahkan.', 'JenisPengelolaan', $data->id);

        return response()->json(['message' => 'Jenis pengelolaan berhasil ditambahkan.', 'data' => $data], 201);
    }

    public function update(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $data = JenisPengelolaan::findOrFail($id);

        $request->validate([
            'nama'      => 'required|string|max:255|unique:jenis_pengelolaans,nama,' . $id,
            'deskripsi' => 'nullable|string',
        ]);

        $data->update($request->only('nama', 'deskripsi'));

        ActivityLog::log('update_jenis_pengelolaan', 'Jenis pengelolaan ' . $data->nama . ' diperbarui.', 'JenisPengelolaan', $id);

        return response()->json(['message' => 'Jenis pengelolaan berhasil diperbarui.', 'data' => $data]);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $data = JenisPengelolaan::findOrFail($id);
        $data->delete();

        ActivityLog::log('delete_jenis_pengelolaan', 'Jenis pengelolaan ' . $data->nama . ' dihapus.', 'JenisPengelolaan', $id);

        return response()->json(['message' => 'Jenis pengelolaan berhasil dihapus.']);
    }
}