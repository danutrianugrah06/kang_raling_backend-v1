<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Edukasi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EdukasiController extends Controller
{
    public function index(Request $request)
    {
        $query = Edukasi::with('user');

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $data = $query->latest()->paginate(10);

        return response()->json($data);
    }

    public function show($slug)
    {
        $data = Edukasi::with('user')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $data]);
    }

    public function edit(Request $request, $id)
    {
        $edukasi = Edukasi::findOrFail($id);

        if ($request->user()->isFasilitator() && $edukasi->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['data' => $edukasi]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul'      => 'required|string|max:255',
            'kategori'   => 'required|in:modul,video',
            'deskripsi'  => 'nullable|string',
            'file_pdf'   => 'nullable|file|mimes:pdf',
            'link_video' => 'nullable|url',
            'gambar'     => 'nullable|image',
        ]);

        if ($request->kategori === 'modul' && !$request->hasFile('file_pdf')) {
            return response()->json([
                'message' => 'File PDF wajib diupload untuk kategori modul.',
            ], 422);
        }

        if ($request->kategori === 'video' && !$request->link_video) {
            return response()->json([
                'message' => 'Link YouTube wajib diisi untuk kategori video.',
            ], 422);
        }

        $slug = Str::slug($request->judul);

        $originalSlug = $slug;
        $count = 1;
        while (Edukasi::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $file_pdf = null;
        if ($request->hasFile('file_pdf')) {
            $file_pdf = $request->file('file_pdf')->store('edukasi/pdf', 'public');
        }

        $gambar = null;
        if ($request->hasFile('gambar')) {
            $gambar = $request->file('gambar')->store('edukasi/gambar', 'public');
        }

        $edukasi = Edukasi::create([
            'user_id'    => $request->user()->id,
            'judul'      => $request->judul,
            'slug'       => $slug,
            'kategori'   => $request->kategori,
            'deskripsi'  => $request->deskripsi,
            'file_pdf'   => $file_pdf,
            'link_video' => $request->link_video,
            'gambar'     => $gambar,
        ]);

        ActivityLog::log('create_edukasi', 'Konten edukasi ' . $edukasi->judul . ' ditambahkan.', 'Edukasi', $edukasi->id);

        return response()->json(['message' => 'Konten edukasi berhasil ditambahkan.', 'data' => $edukasi], 201);
    }

    public function update(Request $request, $id)
    {
        $edukasi = Edukasi::findOrFail($id);

        if ($request->user()->isFasilitator() && $edukasi->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'judul'      => 'required|string|max:255',
            'kategori'   => 'required|in:modul,video',
            'deskripsi'  => 'nullable|string',
            'file_pdf'   => 'nullable|file|mimes:pdf',
            'link_video' => 'nullable|url',
            'gambar'     => 'nullable|image',
        ]);

        $slug = Str::slug($request->judul);

        $originalSlug = $slug;
        $count = 1;
        while (Edukasi::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $file_pdf = $edukasi->file_pdf;
        if ($request->hasFile('file_pdf')) {
            $file_pdf = $request->file('file_pdf')->store('edukasi/pdf', 'public');
        }

        $gambar = $edukasi->gambar;
        if ($request->hasFile('gambar')) {
            $gambar = $request->file('gambar')->store('edukasi/gambar', 'public');
        }

        $edukasi->update([
            'judul'      => $request->judul,
            'slug'       => $slug,
            'kategori'   => $request->kategori,
            'deskripsi'  => $request->deskripsi,
            'file_pdf'   => $file_pdf,
            'link_video' => $request->link_video ?? $edukasi->link_video,
            'gambar'     => $gambar,
        ]);

        ActivityLog::log('update_edukasi', 'Konten edukasi ' . $edukasi->judul . ' diperbarui.', 'Edukasi', $edukasi->id);

        return response()->json(['message' => 'Konten edukasi berhasil diperbarui.', 'data' => $edukasi]);
    }

    public function destroy(Request $request, $id)
    {
        $edukasi = Edukasi::findOrFail($id);

        if ($request->user()->isFasilitator() && $edukasi->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $edukasi->delete();

        ActivityLog::log('delete_edukasi', 'Konten edukasi ' . $edukasi->judul . ' dihapus.', 'Edukasi', $id);

        return response()->json(['message' => 'Konten edukasi berhasil dihapus.']);
    }
}