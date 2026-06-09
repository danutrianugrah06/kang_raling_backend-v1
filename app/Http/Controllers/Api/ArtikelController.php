<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Artikel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArtikelController extends Controller
{
    public function index()
    {
        $data = Artikel::with('user')
            ->where('is_published', true)
            ->latest()
            ->paginate(10);

        return response()->json($data);
    }

    public function show($slug)
    {
        $data = Artikel::with('user')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $data]);
    }

    public function edit(Request $request, $id)
    {
        $artikel = Artikel::findOrFail($id);

        if ($request->user()->isFasilitator() && $artikel->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['data' => $artikel]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul'      => 'required|string|max:255',
            'isi_artikel'=> 'required|string',
            'gambar'     => 'nullable|image|max:10240',
            'is_published' => 'nullable|boolean',
        ]);

        $gambar = null;
        if ($request->hasFile('gambar')) {
            $gambar = $request->file('gambar')->store('artikel', 'public');
        }

        $artikel = Artikel::create([
            'user_id'      => $request->user()->id,
            'judul'        => $request->judul,
            'slug'         => Str::slug($request->judul),
            'isi_artikel'  => $request->isi_artikel,
            'gambar'       => $gambar,
            'is_published' => $request->is_published ?? true,
        ]);

        ActivityLog::log('create_artikel', 'Artikel ' . $artikel->judul . ' ditambahkan.', 'Artikel', $artikel->id);

        return response()->json(['message' => 'Artikel berhasil ditambahkan.', 'data' => $artikel], 201);
    }

    public function update(Request $request, $id)
    {
        $artikel = Artikel::findOrFail($id);

        if ($request->user()->isFasilitator() && $artikel->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'judul'        => 'required|string|max:255',
            'isi_artikel'  => 'required|string',
            'gambar'       => 'nullable|image|max:10240',
            'is_published' => 'nullable|boolean',
        ]);

        $gambar = $artikel->gambar;
        if ($request->hasFile('gambar')) {
            $gambar = $request->file('gambar')->store('artikel', 'public');
        }

        $artikel->update([
            'judul'        => $request->judul,
            'slug'         => Str::slug($request->judul),
            'isi_artikel'  => $request->isi_artikel,
            'gambar'       => $gambar,
            'is_published' => $request->is_published ?? $artikel->is_published,
        ]);

        ActivityLog::log('update_artikel', 'Artikel ' . $artikel->judul . ' diperbarui.', 'Artikel', $artikel->id);

        return response()->json(['message' => 'Artikel berhasil diperbarui.', 'data' => $artikel]);
    }

    public function destroy(Request $request, $id)
    {
        $artikel = Artikel::findOrFail($id);

        if ($request->user()->isFasilitator() && $artikel->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $artikel->delete();

        ActivityLog::log('delete_artikel', 'Artikel ' . $artikel->judul . ' dihapus.', 'Artikel', $id);

        return response()->json(['message' => 'Artikel berhasil dihapus.']);
    }
}