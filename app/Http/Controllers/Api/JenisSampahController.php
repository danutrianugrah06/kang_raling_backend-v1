<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\JenisSampah;
use Illuminate\Http\Request;

class JenisSampahController extends Controller
{
    public function index()
    {
        return response()->json(['data' => JenisSampah::all()]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'nama'      => 'required|string|max:255|unique:jenis_sampahs,nama',
            'deskripsi' => 'nullable|string',
        ]);

        $data = JenisSampah::create($request->only('nama', 'deskripsi'));

        ActivityLog::log('create_jenis_sampah', 'Jenis sampah ' . $data->nama . ' ditambahkan.', 'JenisSampah', $data->id);

        return response()->json(['message' => 'Jenis sampah berhasil ditambahkan.', 'data' => $data], 201);
    }

    public function update(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $data = JenisSampah::findOrFail($id);

        $request->validate([
            'nama'      => 'required|string|max:255|unique:jenis_sampahs,nama,' . $id,
            'deskripsi' => 'nullable|string',
        ]);

        $data->update($request->only('nama', 'deskripsi'));

        ActivityLog::log('update_jenis_sampah', 'Jenis sampah ' . $data->nama . ' diperbarui.', 'JenisSampah', $id);

        return response()->json(['message' => 'Jenis sampah berhasil diperbarui.', 'data' => $data]);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $data = JenisSampah::findOrFail($id);
        $data->delete();

        ActivityLog::log('delete_jenis_sampah', 'Jenis sampah ' . $data->nama . ' dihapus.', 'JenisSampah', $id);

        return response()->json(['message' => 'Jenis sampah berhasil dihapus.']);
    }
}