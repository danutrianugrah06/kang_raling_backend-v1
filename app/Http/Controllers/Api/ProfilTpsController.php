<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProfilTps;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProfilTpsController extends Controller
{

#[OA\Get(
        path: '/profil-tps',
        summary: 'Ambil semua profil TPS',
        description: 'Mengembalikan daftar semua Tempat Penampungan Sampah beserta relasi desanya. Membutuhkan Auth Token.',
        tags: ['Profil TPS'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar profil TPS berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'desa_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'nama_tps', type: 'string', example: 'TPS Sukamaju 01'),
                                    new OA\Property(property: 'nama_pengelola', type: 'string', example: 'Budi Santoso'),
                                    new OA\Property(property: 'nama_fasilitator', type: 'string', nullable: true, example: 'Siti Rahayu'),
                                    new OA\Property(property: 'jumlah_warga_terlayani', type: 'integer', example: 250),
                                    new OA\Property(property: 'kegiatan_tps', type: 'string', nullable: true, example: '3R, Kompos'),
                                    new OA\Property(property: 'telepon', type: 'string', nullable: true, example: '08123456789'),
                                    new OA\Property(property: 'gambar', type: 'string', nullable: true, example: 'profil-tps/foto.jpg'),
                                    new OA\Property(property: 'desa', type: 'object'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.desa-binaan'),
        ]
    )]

    public function index()
    {
        $data = ProfilTps::with('desa')->get();

        return response()->json(['data' => $data]);
    }

    #[OA\Get(
        path: '/profil-tps/{id}',
        summary: 'Ambil detail profil TPS',
        description: 'Mengembalikan detail lengkap satu profil TPS berdasarkan ID beserta relasi desanya.',
        tags: ['Profil TPS'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID profil TPS',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail profil TPS berhasil diambil'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 404, description: 'Profil TPS tidak ditemukan'),
        ]
    )]

    public function show($id)
    {
        $data = ProfilTps::with('desa')->findOrFail($id);

        return response()->json(['data' => $data]);
    }

    #[OA\Post(
        path: '/profil-tps',
        summary: 'Tambah profil TPS baru',
        description: 'Menambahkan profil TPS baru untuk desa tertentu. Gunakan `multipart/form-data` karena mendukung upload gambar.',
        tags: ['Profil TPS'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['desa_id', 'nama_tps', 'nama_pengelola', 'jumlah_warga_terlayani'],
                    properties: [
                        new OA\Property(property: 'desa_id', type: 'integer', example: 1, description: 'ID desa yang memiliki TPS ini'),
                        new OA\Property(property: 'nama_tps', type: 'string', example: 'TPS Sukamaju 01'),
                        new OA\Property(property: 'nama_pengelola', type: 'string', example: 'Budi Santoso'),
                        new OA\Property(property: 'nama_fasilitator', type: 'string', nullable: true, example: 'Siti Rahayu'),
                        new OA\Property(property: 'jumlah_warga_terlayani', type: 'integer', example: 250),
                        new OA\Property(property: 'kegiatan_tps', type: 'string', nullable: true, example: '3R, Kompos, Bank Sampah'),
                        new OA\Property(property: 'telepon', type: 'string', nullable: true, example: '08123456789'),
                        new OA\Property(property: 'gambar', type: 'string', format: 'binary', nullable: true, description: 'Foto TPS'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Profil TPS berhasil ditambahkan'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.desa-binaan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]

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

    #[OA\Post(
        path: '/profil-tps/{id}',
        summary: 'Update profil TPS (gunakan _method=PUT)',
        description: 'Mengupdate data profil TPS. Karena ada upload gambar, gunakan `multipart/form-data` dengan field `_method: PUT`.',
        tags: ['Profil TPS'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['desa_id', 'nama_tps', 'nama_pengelola', 'jumlah_warga_terlayani', '_method'],
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Wajib diisi PUT untuk method spoofing'),
                        new OA\Property(property: 'desa_id', type: 'integer', example: 1),
                        new OA\Property(property: 'nama_tps', type: 'string', example: 'TPS Sukamaju 01 (Revisi)'),
                        new OA\Property(property: 'nama_pengelola', type: 'string', example: 'Budi Santoso'),
                        new OA\Property(property: 'nama_fasilitator', type: 'string', nullable: true),
                        new OA\Property(property: 'jumlah_warga_terlayani', type: 'integer', example: 300),
                        new OA\Property(property: 'kegiatan_tps', type: 'string', nullable: true),
                        new OA\Property(property: 'telepon', type: 'string', nullable: true),
                        new OA\Property(property: 'gambar', type: 'string', format: 'binary', nullable: true, description: 'Kosongkan jika tidak ganti foto'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profil TPS berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.desa-binaan'),
            new OA\Response(response: 404, description: 'Profil TPS tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]

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

    #[OA\Delete(
        path: '/profil-tps/{id}',
        summary: 'Hapus profil TPS',
        description: 'Menghapus profil TPS secara permanen. Khusus Koordinator.',
        tags: ['Profil TPS'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil TPS berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Profil TPS berhasil dihapus.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Koordinator'),
            new OA\Response(response: 404, description: 'Profil TPS tidak ditemukan'),
        ]
    )]

    // app/Http/Controllers/Api/ProfilTpsController.php
    // GANTI HANYA method destroy() dengan ini

    public function destroy(Request $request, $id)
    {
        // Ganti isAdmin() lama ke hasRole() Spatie
        if (!$request->user()->hasRole('Koordinator')) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $profil = ProfilTps::findOrFail($id);
        $profil->delete();

        ActivityLog::log('delete_profil_tps', 'Profil TPS ' . $profil->nama_tps . ' dihapus.', 'ProfilTps', $id);

        return response()->json(['message' => 'Profil TPS berhasil dihapus.']);
    }
}
