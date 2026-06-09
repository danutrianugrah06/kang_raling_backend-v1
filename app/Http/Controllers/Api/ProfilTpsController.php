<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProfilTps;
use Illuminate\Http\Request;

class ProfilTpsController extends Controller
{
    public function index()
    {
        $data = ProfilTps::with('desa')->get();

        return response()->json(['data' => $data]);
    }

    public function show($id)
    {
        $data = ProfilTps::with('desa')->findOrFail($id);

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'desa_id'                => 'required|exists:desas,id',
            'nama_tps'               => 'required|string|max:255',
            'nama_pengelola'         => 'required|string|max:255',
            'nama_fasilitator'       => 'nullable|string|max:255',
            'jumlah_warga_terlayani' => 'required|integer|min:0',
            'kegiatan_tps'           => 'nullable|string',
            'telepon'                => 'nullable|string|max:20',
            'gambar'                 => 'nullable|image',
        ]);

        $gambar = null;
        if ($request->hasFile('gambar')) {
            $gambar = $request->file('gambar')->store('profil-tps', 'public');
        }

        $profil = ProfilTps::create([
            'desa_id'                => $request->desa_id,
            'nama_tps'               => $request->nama_tps,
            'nama_pengelola'         => $request->nama_pengelola,
            'nama_fasilitator'       => $request->nama_fasilitator,
            'jumlah_warga_terlayani' => $request->jumlah_warga_terlayani,
            'kegiatan_tps'           => $request->kegiatan_tps,
            'telepon'                => $request->telepon,
            'gambar'                 => $gambar,
        ]);

        ActivityLog::log('create_profil_tps', 'Profil TPS ' . $profil->nama_tps . ' ditambahkan.', 'ProfilTps', $profil->id);

        return response()->json(['message' => 'Profil TPS berhasil ditambahkan.', 'data' => $profil], 201);
    }

    public function update(Request $request, $id)
    {
        $profil = ProfilTps::findOrFail($id);

        $request->validate([
            'desa_id'                => 'required|exists:desas,id',
            'nama_tps'               => 'required|string|max:255',
            'nama_pengelola'         => 'required|string|max:255',
            'nama_fasilitator'       => 'nullable|string|max:255',
            'jumlah_warga_terlayani' => 'required|integer|min:0',
            'kegiatan_tps'           => 'nullable|string',
            'telepon'                => 'nullable|string|max:20',
            'gambar'                 => 'nullable|image',
        ]);

        $gambar = $profil->gambar;
        if ($request->hasFile('gambar')) {
            $gambar = $request->file('gambar')->store('profil-tps', 'public');
        }

        $profil->update([
            'desa_id'                => $request->desa_id,
            'nama_tps'               => $request->nama_tps,
            'nama_pengelola'         => $request->nama_pengelola,
            'nama_fasilitator'       => $request->nama_fasilitator,
            'jumlah_warga_terlayani' => $request->jumlah_warga_terlayani,
            'kegiatan_tps'           => $request->kegiatan_tps,
            'telepon'                => $request->telepon,
            'gambar'                 => $gambar,
        ]);

        ActivityLog::log('update_profil_tps', 'Profil TPS ' . $profil->nama_tps . ' diperbarui.', 'ProfilTps', $profil->id);

        return response()->json(['message' => 'Profil TPS berhasil diperbarui.', 'data' => $profil]);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $profil = ProfilTps::findOrFail($id);
        $profil->delete();

        ActivityLog::log('delete_profil_tps', 'Profil TPS ' . $profil->nama_tps . ' dihapus.', 'ProfilTps', $id);

        return response()->json(['message' => 'Profil TPS berhasil dihapus.']);
    }
}