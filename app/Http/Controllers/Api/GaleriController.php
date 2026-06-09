<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Galeri;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GaleriController extends Controller
{
    public function index()
    {
        $data = Galeri::with('user')->latest()->paginate(12);

        return response()->json($data);
    }

    public function show($slug)
    {
        $data = Galeri::with('user')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'gambar'     => 'required|image',
            'keterangan' => 'nullable|string',
        ]);

        $gambar = $request->file('gambar')->store('galeri', 'public');

        $keterangan = $request->keterangan;
        $slug = Str::slug($keterangan ?? 'galeri-' . now()->timestamp);

        // Pastikan slug unik
        $originalSlug = $slug;
        $count = 1;
        while (Galeri::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $galeri = Galeri::create([
            'user_id'    => $request->user()->id,
            'gambar'     => $gambar,
            'slug'       => $slug,
            'keterangan' => $keterangan,
        ]);

        ActivityLog::log('create_galeri', 'Foto galeri ditambahkan.', 'Galeri', $galeri->id);

        return response()->json(['message' => 'Foto berhasil diunggah.', 'data' => $galeri], 201);
    }

    public function update(Request $request, $id)
    {
        $galeri = Galeri::findOrFail($id);

        // Mencegah fasilitator mengedit foto milik orang lain
        if ($request->user()->isFasilitator() && $galeri->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // Validasi data: gambar boleh kosong (jika tidak mau ganti foto)
        $request->validate([
            'gambar'     => 'nullable|image',
            'keterangan' => 'nullable|string',
        ]);

        // Tetapkan keterangan baru, atau gunakan yang lama jika tidak diubah
        $keterangan = $request->keterangan ?? $galeri->keterangan;
        
        // Buat slug baru berdasarkan keterangan yang baru
        $slug = Str::slug($keterangan ?? 'galeri-' . now()->timestamp);

        // Pastikan slug unik (tidak bentrok dengan galeri foto lain)
        $originalSlug = $slug;
        $count = 1;
        while (Galeri::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        // Cek apakah ada file gambar baru yang diunggah
        $gambar = $galeri->gambar; // Default: pakai nama file gambar lama
        if ($request->hasFile('gambar')) {
            // Timpa dengan gambar baru
            $gambar = $request->file('gambar')->store('galeri', 'public');
        }

        // Simpan perubahan ke database
        $galeri->update([
            'gambar'     => $gambar,
            'slug'       => $slug,
            'keterangan' => $keterangan,
        ]);

        // Catat aktivitas untuk log admin
        ActivityLog::log('update_galeri', 'Foto galeri diperbarui.', 'Galeri', $galeri->id);

        return response()->json(['message' => 'Foto berhasil diperbarui.', 'data' => $galeri]);
    }

    public function destroy(Request $request, $id)
    {
        $galeri = Galeri::findOrFail($id);

        if ($request->user()->isFasilitator() && $galeri->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $galeri->delete();

        ActivityLog::log('delete_galeri', 'Foto galeri ID ' . $id . ' dihapus.', 'Galeri', $id);

        return response()->json(['message' => 'Foto berhasil dihapus.']);
    }
}