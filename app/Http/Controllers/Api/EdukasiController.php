<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Edukasi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class EdukasiController extends Controller
{

#[OA\Get(
        path: '/api/v1/edukasis',
        summary: 'Ambil semua konten edukasi',
        description: 'Mengembalikan daftar konten edukasi untuk halaman publik. Mendukung filter berdasarkan kategori (`modul` atau `video`). Tidak membutuhkan token.',
        tags: ['Publik'],
        parameters: [
            new OA\Parameter(name: 'kategori', in: 'query', required: false, description: 'Filter kategori konten', schema: new OA\Schema(type: 'string', enum: ['modul', 'video'])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar konten edukasi berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'judul', type: 'string', example: 'Modul Pemilahan Sampah'),
                                new OA\Property(property: 'slug', type: 'string', example: 'modul-pemilahan-sampah'),
                                new OA\Property(property: 'kategori', type: 'string', enum: ['modul', 'video'], example: 'modul'),
                                new OA\Property(property: 'deskripsi', type: 'string', nullable: true),
                                new OA\Property(property: 'file_pdf', type: 'string', nullable: true),
                                new OA\Property(property: 'link_video', type: 'string', nullable: true),
                                new OA\Property(property: 'gambar', type: 'string', nullable: true),
                            ],
                            type: 'object'
                        )),
                        new OA\Property(property: 'total', type: 'integer', example: 5),
                    ]
                )
            ),
        ]
    )]

    public function index(Request $request)
    {
        $query = Edukasi::with('user');

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $data = $query->latest()->paginate(10);

        return response()->json($data);
    }

    #[OA\Get(
        path: '/api/v1/edukasis/{slug}',
        summary: 'Ambil detail konten edukasi berdasarkan slug',
        description: 'Mengembalikan detail lengkap satu konten edukasi. Tidak membutuhkan token.',
        tags: ['Publik'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'modul-pemilahan-sampah')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail konten edukasi berhasil diambil'),
            new OA\Response(response: 404, description: 'Konten edukasi tidak ditemukan'),
        ]
    )]

    public function show($slug)
    {
        $data = Edukasi::with('user')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $data]);
    }

    #[OA\Get(
        path: '/api/v1/edukasis/{id}/edit',
        summary: 'Ambil data edukasi untuk form edit (dashboard)',
        description: 'Mengambil data konten edukasi berdasarkan ID untuk form edit di dashboard. Fasilitator hanya bisa akses konten miliknya sendiri.',
        tags: ['Edukasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data edukasi untuk edit berhasil diambil'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
            new OA\Response(response: 404, description: 'Konten edukasi tidak ditemukan'),
        ]
    )]

    public function edit(Request $request, $id)
    {
        $edukasi = Edukasi::findOrFail($id);

        if ($request->user()->isFasilitator() && $edukasi->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['data' => $edukasi]);
    }

    #[OA\Post(
        path: '/api/v1/edukasis',
        summary: 'Tambah konten edukasi baru',
        description: "Menambahkan konten edukasi baru.\n\n**Aturan kategori:**\n- Kategori `modul` → wajib upload `file_pdf`\n- Kategori `video` → wajib isi `link_video` (URL YouTube)\n\nGunakan `multipart/form-data` karena ada upload file.",
        tags: ['Edukasi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['judul', 'kategori'],
                    properties: [
                        new OA\Property(property: 'judul', type: 'string', example: 'Modul Pemilahan Sampah Organik'),
                        new OA\Property(property: 'kategori', type: 'string', enum: ['modul', 'video'], example: 'modul'),
                        new OA\Property(property: 'deskripsi', type: 'string', nullable: true, example: 'Panduan lengkap pemilahan sampah organik'),
                        new OA\Property(property: 'file_pdf', type: 'string', format: 'binary', nullable: true, description: 'Wajib jika kategori = modul'),
                        new OA\Property(property: 'link_video', type: 'string', nullable: true, example: 'https://youtube.com/watch?v=xxx', description: 'Wajib jika kategori = video'),
                        new OA\Property(property: 'gambar', type: 'string', format: 'binary', nullable: true, description: 'Gambar thumbnail'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Konten edukasi berhasil ditambahkan'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.edukasi'),
            new OA\Response(response: 422, description: 'Validasi gagal — PDF tidak diupload untuk modul / link video kosong untuk video'),
        ]
    )]

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

    #[OA\Put(
        path: '/api/v1/edukasis/{id}',
        summary: 'Update konten edukasi',
        description: 'Mengupdate konten edukasi yang sudah ada. Gunakan `multipart/form-data` dengan `_method: PUT`.',
        tags: ['Edukasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['judul', 'kategori'],
                    properties: [
                        new OA\Property(property: 'judul', type: 'string', example: 'Modul Pemilahan Sampah Organik (Revisi)'),
                        new OA\Property(property: 'kategori', type: 'string', enum: ['modul', 'video'], example: 'modul'),
                        new OA\Property(property: 'deskripsi', type: 'string', nullable: true),
                        new OA\Property(property: 'file_pdf', type: 'string', format: 'binary', nullable: true, description: 'Kosongkan jika tidak ganti PDF'),
                        new OA\Property(property: 'link_video', type: 'string', nullable: true),
                        new OA\Property(property: 'gambar', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Konten edukasi berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
            new OA\Response(response: 404, description: 'Konten edukasi tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]

    // app/Http/Controllers/Api/EdukasiController.php
    // GANTI HANYA method update() dengan ini

    public function update(Request $request, $id)
    {
        $edukasi = Edukasi::findOrFail($id);

        // Ganti isFasilitator() lama ke hasRole() Spatie
        if (
            $request->user()->hasRole('Fasilitator')
            && !$request->user()->hasRole('Administrator')
            && $edukasi->user_id !== $request->user()->id
        ) {
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

    #[OA\Delete(
        path: '/api/v1/edukasis/{id}',
        summary: 'Hapus konten edukasi',
        description: 'Menghapus konten edukasi secara permanen. Fasilitator hanya bisa hapus konten miliknya sendiri.',
        tags: ['Edukasi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Konten edukasi berhasil dihapus'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
            new OA\Response(response: 404, description: 'Konten edukasi tidak ditemukan'),
        ]
    )]

    // app/Http/Controllers/Api/EdukasiController.php
    // GANTI HANYA method destroy() dengan ini

    public function destroy(Request $request, $id)
    {
        $edukasi = Edukasi::findOrFail($id);

        // Ganti isFasilitator() lama ke hasRole() Spatie
        if (
            $request->user()->hasRole('Fasilitator')
            && !$request->user()->hasRole('Administrator')
            && $edukasi->user_id !== $request->user()->id
        ) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $edukasi->delete();

        ActivityLog::log('delete_edukasi', 'Konten edukasi ' . $edukasi->judul . ' dihapus.', 'Edukasi', $id);

        return response()->json(['message' => 'Konten edukasi berhasil dihapus.']);
    }
}
