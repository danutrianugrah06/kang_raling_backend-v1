<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\JenisSampah;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class JenisSampahController extends Controller
{

#[OA\Get(
        path: '/jenis-sampah',
        summary: 'Ambil semua jenis sampah',
        description: 'Mengembalikan daftar semua jenis sampah yang tersedia di sistem. Data ini digunakan sebagai referensi saat input data sampah. Membutuhkan Auth Token.',
        tags: ['Jenis Sampah'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar jenis sampah berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'nama', type: 'string', example: 'Sampah Organik'),
                                    new OA\Property(property: 'deskripsi', type: 'string', nullable: true, example: 'Sampah yang dapat terurai secara alami'),
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
        return response()->json(['data' => JenisSampah::all()]);
    }

    #[OA\Post(
        path: '/jenis-sampah',
        summary: 'Tambah jenis sampah baru',
        description: 'Menambahkan jenis sampah baru ke master data. Khusus Koordinator dengan permission `kelola.jenis-sampah`. Nama jenis sampah harus unik.',
        tags: ['Jenis Sampah'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama'],
                properties: [
                    new OA\Property(
                        property: 'nama',
                        type: 'string',
                        example: 'Sampah Organik',
                        description: 'Nama jenis sampah, harus unik'
                    ),
                    new OA\Property(
                        property: 'deskripsi',
                        type: 'string',
                        nullable: true,
                        example: 'Sampah yang dapat terurai secara alami seperti sisa makanan dan daun'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Jenis sampah berhasil ditambahkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Jenis sampah berhasil ditambahkan.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama', type: 'string', example: 'Sampah Organik'),
                                new OA\Property(property: 'deskripsi', type: 'string', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Koordinator'),
            new OA\Response(response: 422, description: 'Validasi gagal — nama sudah dipakai'),
        ]
    )]

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

    #[OA\Put(
        path: '/jenis-sampah/{id}',
        summary: 'Update jenis sampah',
        description: 'Mengupdate nama dan deskripsi jenis sampah. Nama harus tetap unik (boleh sama dengan nama saat ini). Khusus Koordinator.',
        tags: ['Jenis Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID jenis sampah',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string', example: 'Sampah Organik (Diperbarui)'),
                    new OA\Property(property: 'deskripsi', type: 'string', nullable: true, example: 'Deskripsi yang diperbarui'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Jenis sampah berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Jenis sampah berhasil diperbarui.'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Koordinator'),
            new OA\Response(response: 404, description: 'Jenis sampah tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal — nama sudah dipakai oleh jenis sampah lain'),
        ]
    )]

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

    #[OA\Delete(
        path: '/jenis-sampah/{id}',
        summary: 'Hapus jenis sampah',
        description: 'Menghapus jenis sampah dari master data secara permanen. Pastikan jenis sampah ini tidak sedang digunakan di data sampah manapun sebelum dihapus. Khusus Koordinator.',
        tags: ['Jenis Sampah'],
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
                description: 'Jenis sampah berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Jenis sampah berhasil dihapus.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Koordinator'),
            new OA\Response(response: 404, description: 'Jenis sampah tidak ditemukan'),
        ]
    )]

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