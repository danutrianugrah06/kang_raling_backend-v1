<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\JenisPengelolaan;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class JenisPengelolaanController extends Controller
{

#[OA\Get(
        path: '/jenis-pengelolaan',
        summary: 'Ambil semua jenis pengelolaan sampah',
        description: 'Mengembalikan daftar semua jenis pengelolaan sampah yang tersedia (contoh: 3R, Kompos, Bank Sampah). Data ini digunakan sebagai referensi saat input data pengelolaan. Membutuhkan Auth Token.',
        tags: ['Jenis Pengelolaan'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar jenis pengelolaan berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'nama', type: 'string', example: 'Kompos'),
                                    new OA\Property(property: 'deskripsi', type: 'string', nullable: true, example: 'Pengolahan sampah organik menjadi pupuk kompos'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid atau tidak diberikan'),
        ]
    )]

    public function index()
    {
        return response()->json(['data' => JenisPengelolaan::all()]);
    }

    #[OA\Post(
        path: '/jenis-pengelolaan',
        summary: 'Tambah jenis pengelolaan sampah baru',
        description: 'Menambahkan jenis pengelolaan sampah baru ke master data. Khusus Administrator dengan permission `kelola.jenis-pengelolaan`. Nama harus unik.',
        tags: ['Jenis Pengelolaan'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama'],
                properties: [
                    new OA\Property(
                        property: 'nama',
                        type: 'string',
                        example: 'Kompos',
                        description: 'Nama jenis pengelolaan, harus unik. Contoh: 3R, Kompos, Bank Sampah, Daur Ulang'
                    ),
                    new OA\Property(
                        property: 'deskripsi',
                        type: 'string',
                        nullable: true,
                        example: 'Pengolahan sampah organik menjadi pupuk kompos yang berguna'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Jenis pengelolaan berhasil ditambahkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Jenis pengelolaan berhasil ditambahkan.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama', type: 'string', example: 'Kompos'),
                                new OA\Property(property: 'deskripsi', type: 'string', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Administrator'),
            new OA\Response(response: 422, description: 'Validasi gagal — nama sudah dipakai'),
        ]
    )]

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

    #[OA\Put(
        path: '/jenis-pengelolaan/{id}',
        summary: 'Update jenis pengelolaan sampah',
        description: 'Mengupdate nama dan deskripsi jenis pengelolaan. Nama harus tetap unik. Khusus Administrator.',
        tags: ['Jenis Pengelolaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID jenis pengelolaan',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string', example: 'Kompos (Diperbarui)'),
                    new OA\Property(property: 'deskripsi', type: 'string', nullable: true, example: 'Deskripsi yang diperbarui'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Jenis pengelolaan berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Jenis pengelolaan berhasil diperbarui.'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Administrator'),
            new OA\Response(response: 404, description: 'Jenis pengelolaan tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal — nama sudah dipakai oleh jenis pengelolaan lain'),
        ]
    )]

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

    #[OA\Delete(
        path: '/jenis-pengelolaan/{id}',
        summary: 'Hapus jenis pengelolaan sampah',
        description: 'Menghapus jenis pengelolaan dari master data secara permanen. Pastikan tidak sedang digunakan di data pengelolaan manapun. Khusus Administrator.',
        tags: ['Jenis Pengelolaan'],
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
                description: 'Jenis pengelolaan berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Jenis pengelolaan berhasil dihapus.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Administrator'),
            new OA\Response(response: 404, description: 'Jenis pengelolaan tidak ditemukan'),
        ]
    )]

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