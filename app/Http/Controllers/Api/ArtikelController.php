<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Artikel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ArtikelController extends Controller
{

#[OA\Get(
        path: '/api/v1/artikels',
        summary: 'Ambil semua artikel yang dipublikasikan',
        description: 'Mengembalikan daftar artikel yang sudah dipublikasikan (`is_published: true`) untuk ditampilkan di halaman publik. Tidak membutuhkan token.',
        tags: ['Publik'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar artikel berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'judul', type: 'string', example: 'Cara Memilah Sampah di Rumah'),
                                new OA\Property(property: 'slug', type: 'string', example: 'cara-memilah-sampah-di-rumah'),
                                new OA\Property(property: 'gambar', type: 'string', nullable: true, example: 'artikel/foto.jpg'),
                                new OA\Property(property: 'is_published', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        )),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'total', type: 'integer', example: 8),
                    ]
                )
            ),
        ]
    )]

    public function index()
    {
        $data = Artikel::with('user')
            ->where('is_published', true)
            ->latest()
            ->paginate(10);

        return response()->json($data);
    }

    #[OA\Get(
        path: '/api/v1/artikels/{slug}',
        summary: 'Ambil detail artikel berdasarkan slug',
        description: 'Mengembalikan detail lengkap satu artikel berdasarkan slug. Digunakan untuk halaman detail artikel publik.',
        tags: ['Publik'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, description: 'Slug artikel', schema: new OA\Schema(type: 'string', example: 'cara-memilah-sampah-di-rumah')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail artikel berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'judul', type: 'string', example: 'Cara Memilah Sampah di Rumah'),
                                new OA\Property(property: 'isi_artikel', type: 'string', example: 'Isi artikel lengkap...'),
                                new OA\Property(property: 'gambar', type: 'string', nullable: true),
                                new OA\Property(property: 'user', type: 'object'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Artikel tidak ditemukan'),
        ]
    )]

    public function show($slug)
    {
        $data = Artikel::with('user')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $data]);
    }

     #[OA\Get(
        path: '/api/v1/artikels/{id}/edit',
        summary: 'Ambil data artikel untuk form edit (dashboard)',
        description: 'Mengambil data artikel berdasarkan ID untuk keperluan form edit di dashboard. Fasilitator hanya bisa akses artikel miliknya sendiri.',
        tags: ['Artikel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID artikel', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data artikel untuk edit berhasil diambil'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Fasilitator mencoba edit artikel milik orang lain'),
            new OA\Response(response: 404, description: 'Artikel tidak ditemukan'),
        ]
    )]

    public function edit(Request $request, $id)
    {
        $artikel = Artikel::findOrFail($id);

        if ($request->user()->isFasilitator() && $artikel->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['data' => $artikel]);
    }

    #[OA\Post(
        path: '/api/v1/artikels',
        summary: 'Tambah artikel baru',
        description: 'Menambahkan artikel baru. Request menggunakan `multipart/form-data` karena mendukung upload gambar.',
        tags: ['Artikel'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['judul', 'isi_artikel'],
                    properties: [
                        new OA\Property(property: 'judul', type: 'string', example: 'Cara Memilah Sampah di Rumah'),
                        new OA\Property(property: 'isi_artikel', type: 'string', example: 'Isi artikel lengkap di sini...'),
                        new OA\Property(property: 'gambar', type: 'string', format: 'binary', nullable: true, description: 'File gambar (maks 10MB)'),
                        new OA\Property(property: 'is_published', type: 'boolean', example: true, nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Artikel berhasil ditambahkan'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.artikel'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]

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

    #[OA\Post(
        path: '/api/v1/artikels/{id}',
        summary: 'Update artikel (gunakan method spoofing _method=PUT)',
        description: 'Mengupdate artikel yang sudah ada. Karena ada upload file, gunakan `multipart/form-data` dengan tambahan field `_method: PUT` untuk method spoofing.',
        tags: ['Artikel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['judul', 'isi_artikel', '_method'],
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Wajib diisi PUT untuk method spoofing'),
                        new OA\Property(property: 'judul', type: 'string', example: 'Cara Memilah Sampah di Rumah'),
                        new OA\Property(property: 'isi_artikel', type: 'string', example: 'Isi artikel yang diperbarui...'),
                        new OA\Property(property: 'gambar', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'is_published', type: 'boolean', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Artikel berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
            new OA\Response(response: 404, description: 'Artikel tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]

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

    #[OA\Delete(
        path: '/api/v1/artikels/{id}',
        summary: 'Hapus artikel',
        description: 'Menghapus artikel secara permanen. Fasilitator hanya bisa hapus artikel miliknya sendiri.',
        tags: ['Artikel'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Artikel berhasil dihapus'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
            new OA\Response(response: 404, description: 'Artikel tidak ditemukan'),
        ]
    )]

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