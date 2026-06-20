<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Galeri;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;


class GaleriController extends Controller
{

#[OA\Get(
        path: '/api/v1/galeris',
        summary: 'Ambil semua foto galeri',
        description: 'Mengembalikan daftar semua foto galeri untuk halaman publik. Tidak membutuhkan token. Hasil dipaginasi 12 foto per halaman.',
        tags: ['Publik'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar foto galeri berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'gambar', type: 'string', example: 'galeri/foto.jpg'),
                                new OA\Property(property: 'slug', type: 'string', example: 'kegiatan-bersih-sungai'),
                                new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Kegiatan bersih sungai Desa Sukamaju'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ],
                            type: 'object'
                        )),
                        new OA\Property(property: 'total', type: 'integer', example: 24),
                    ]
                )
            ),
        ]
    )]

    public function index()
    {
        $data = Galeri::with('user')->latest()->paginate(12);

        return response()->json($data);
    }

     #[OA\Get(
        path: '/api/v1/galeris/{slug}',
        summary: 'Ambil detail foto galeri berdasarkan slug',
        description: 'Mengembalikan detail satu foto galeri berdasarkan slug. Tidak membutuhkan token.',
        tags: ['Publik'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'kegiatan-bersih-sungai')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail foto galeri berhasil diambil'),
            new OA\Response(response: 404, description: 'Foto tidak ditemukan'),
        ]
    )]

    public function show($slug)
    {
        $data = Galeri::with('user')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $data]);
    }

    #[OA\Post(
        path: '/api/v1/galeris',
        summary: 'Upload foto galeri baru',
        description: 'Mengunggah foto galeri baru. Request HARUS menggunakan `multipart/form-data` karena ada upload file gambar.',
        tags: ['Galeri'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['gambar'],
                    properties: [
                        new OA\Property(property: 'gambar', type: 'string', format: 'binary', description: 'File gambar yang akan diunggah (jpg, png, dll)'),
                        new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Kegiatan bersih sungai Desa Sukamaju'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Foto berhasil diunggah'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.galeri'),
            new OA\Response(response: 422, description: 'Validasi gagal — file bukan gambar'),
        ]
    )]

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

     #[OA\Post(
        path: '/api/v1/galeris/{id}',
        summary: 'Update foto galeri (gunakan _method=PUT)',
        description: 'Mengupdate keterangan atau mengganti foto galeri. Gunakan `multipart/form-data` dengan field `_method: PUT`.',
        tags: ['Galeri'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['_method'],
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Wajib diisi PUT untuk method spoofing'),
                        new OA\Property(property: 'gambar', type: 'string', format: 'binary', nullable: true, description: 'Kosongkan jika tidak ingin ganti foto'),
                        new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Kegiatan bersih sungai (diperbarui)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Foto berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
            new OA\Response(response: 404, description: 'Foto tidak ditemukan'),
        ]
    )]

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

    #[OA\Delete(
        path: '/api/v1/galeris/{id}',
        summary: 'Hapus foto galeri',
        description: 'Menghapus foto galeri secara permanen. Fasilitator hanya bisa hapus foto miliknya sendiri.',
        tags: ['Galeri'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Foto berhasil dihapus'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
            new OA\Response(response: 404, description: 'Foto tidak ditemukan'),
        ]
    )]

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