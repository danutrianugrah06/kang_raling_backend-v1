<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DataPengelolaanSampah;
use App\Models\DataSampah;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DataPengelolaanSampahController extends Controller
{

#[OA\Get(
        path: '/data-pengelolaan',
        summary: 'Ambil semua data pengelolaan sampah',
        description: 'Mengembalikan daftar data pengelolaan sampah. Fasilitator hanya melihat data miliknya sendiri. Administrator melihat semua data. Mendukung pagination.',
        tags: ['Data Pengelolaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Jumlah data per halaman (default: 10)', schema: new OA\Schema(type: 'integer', example: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar data pengelolaan berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'data_sampah_id', type: 'integer', example: 5),
                                        new OA\Property(property: 'jenis_pengelolaan_id', type: 'integer', example: 2),
                                        new OA\Property(property: 'user_id', type: 'integer', example: 3),
                                        new OA\Property(property: 'jumlah', type: 'number', format: 'float', example: 50.5),
                                        new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Kompos dari sampah organik'),
                                    ],
                                    type: 'object'
                                )),
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 25),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid atau tidak diberikan'),
            new OA\Response(response: 403, description: 'Tidak punya permission input.data-pengelolaan'),
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = DataPengelolaanSampah::with([
            'dataSampah.desa',
            'dataSampah.jenisSampah',
            'jenisPengelolaan',
            'user'
        ]);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Administrator')) {
            $query->where('user_id', $user->id);
        }

        $data = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json(['status' => true, 'data' => $data]);
    }

    #[OA\Get(
        path: '/data-pengelolaan/{id}',
        summary: 'Ambil detail satu data pengelolaan',
        description: 'Mengembalikan detail lengkap satu data pengelolaan sampah. Fasilitator hanya bisa melihat data miliknya sendiri.',
        tags: ['Data Pengelolaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID data pengelolaan', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail data pengelolaan berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Fasilitator mencoba akses data milik pengguna lain'),
            new OA\Response(response: 404, description: 'Data pengelolaan tidak ditemukan'),
        ]
    )]

    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataPengelolaanSampah::with([
            'dataSampah.desa',
            'dataSampah.jenisSampah',
            'jenisPengelolaan',
            'user'
        ])->findOrFail($id);

        if (
            $user->hasRole('Fasilitator') && !$user->hasRole('Administrator')
            && $data->user_id !== $user->id
        ) {
            return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['status' => true, 'data' => $data]);
    }

    #[OA\Post(
        path: '/data-pengelolaan',
        summary: 'Input data pengelolaan sampah baru',
        description: 'Menambahkan data pengelolaan sampah baru. Data sampah sumber HARUS sudah berstatus `verified`. Jumlah pengelolaan tidak boleh melebihi jumlah pada data sampah sumber.',
        tags: ['Data Pengelolaan'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['data_sampah_id', 'jenis_pengelolaan_id', 'jumlah'],
                properties: [
                    new OA\Property(property: 'data_sampah_id', type: 'integer', example: 5, description: 'ID data sampah sumber yang sudah berstatus verified'),
                    new OA\Property(property: 'jenis_pengelolaan_id', type: 'integer', example: 2, description: 'ID jenis pengelolaan (3R, kompos, dll)'),
                    new OA\Property(property: 'jumlah', type: 'number', format: 'float', example: 50.5, description: 'Jumlah sampah yang dikelola dalam Kg, tidak boleh melebihi jumlah data sampah sumber'),
                    new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Kompos dari sampah organik RT 01'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Data pengelolaan berhasil disimpan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data pengelolaan berhasil disimpan.'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak — data sampah bukan milik fasilitator ini'),
            new OA\Response(response: 422, description: 'Validasi gagal — data sampah belum verified atau jumlah melebihi batas'),
        ]
    )]

    public function store(Request $request): JsonResponse
    {
        // 1. Validasi Tahap 1: Pastikan ID Sampah dikirim dan ada di database
        $request->validate([
            'data_sampah_id' => 'required|exists:data_sampahs,id',
        ]);

        // 2. Tarik Data Sampah Mentahnya
        $dataSampah = DataSampah::findOrFail($request->data_sampah_id);

        // Pengecekan Logika Bisnis: Harus terverifikasi
        if (!$dataSampah->isVerified()) {
            return response()->json([
                'status'  => false,
                'message' => 'Data pengelolaan hanya bisa diinput untuk data sampah yang sudah diverifikasi.',
            ], 422);
        }

        // Pengecekan Hak Akses Fasilitator
        $user = $request->user();
        if (
            $user->hasRole('Fasilitator') && !$user->hasRole('Administrator')
            && $dataSampah->user_id !== $user->id
        ) {
            return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
        }

        // 3. Validasi Tahap 2: Gembok Maksimal
        $request->validate([
            'jenis_pengelolaan_id' => 'required|exists:jenis_pengelolaans,id',
            'jumlah'               => 'required|numeric|min:0.01|max:' . $dataSampah->jumlah,
            'keterangan'           => 'nullable|string',
        ], [
            // Pesan khusus jika input fasilitator melewati batas
            'jumlah.max' => 'Jumlah pengelolaan tidak boleh melebihi sumber sampah (' . (float)$dataSampah->jumlah . ' Kg).'
        ]);

        // 4. Simpan Data
        $data = DataPengelolaanSampah::create([
            'data_sampah_id'       => $request->data_sampah_id,
            'jenis_pengelolaan_id' => $request->jenis_pengelolaan_id,
            'user_id'              => $user->id,
            'jumlah'               => $request->jumlah,
            'keterangan'           => $request->keterangan,
        ]);

        ActivityLog::log(
            'create_pengelolaan',
            'Data pengelolaan sampah ditambahkan.',
            'DataPengelolaanSampah',
            $data->id
        );

        return response()->json([
            'status'  => true,
            'message' => 'Data pengelolaan berhasil disimpan.',
            'data'    => $data,
        ], 201);
    }

    #[OA\Put(
        path: '/data-pengelolaan/{id}',
        summary: 'Update data pengelolaan sampah',
        description: 'Mengupdate data pengelolaan yang sudah ada. Jumlah tidak boleh melebihi jumlah data sampah sumber.',
        tags: ['Data Pengelolaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['jenis_pengelolaan_id', 'jumlah'],
                properties: [
                    new OA\Property(property: 'jenis_pengelolaan_id', type: 'integer', example: 2),
                    new OA\Property(property: 'jumlah', type: 'number', format: 'float', example: 45.0),
                    new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Diperbarui: kompos RT 01 dan RT 02'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Data pengelolaan berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
            new OA\Response(response: 404, description: 'Data pengelolaan tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal — jumlah melebihi batas maksimal'),
        ]
    )]

    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        
        // 1. Tarik data pengelolaan SEKALIGUS relasi data sampah aslinya
        $data = DataPengelolaanSampah::with('dataSampah')->findOrFail($id);

        if (
            $user->hasRole('Fasilitator') && !$user->hasRole('Administrator')
            && $data->user_id !== $user->id
        ) {
            return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
        }

        // 2. Ambil nilai maksimal dari sumber sampah aslinya
        // Jika karena suatu alasan datanya tidak ketemu, berikan fallback nilai besar (aman)
        $batasMaksimal = $data->dataSampah ? $data->dataSampah->jumlah : 999999;

        // 3. Validasi Edit: Gembok Maksimal
        $request->validate([
            'jenis_pengelolaan_id' => 'required|exists:jenis_pengelolaans,id',
            'jumlah'               => 'required|numeric|min:0.01|max:' . $batasMaksimal,
            'keterangan'           => 'nullable|string',
        ], [
            'jumlah.max' => 'Jumlah pengelolaan tidak boleh melebihi sumber sampah (' . (float)$batasMaksimal . ' Kg).'
        ]);

        // 4. Update Data
        $data->update($request->only('jenis_pengelolaan_id', 'jumlah', 'keterangan'));

        ActivityLog::log(
            'update_pengelolaan',
            'Data pengelolaan sampah ID ' . $id . ' diperbarui.',
            'DataPengelolaanSampah',
            $id
        );

        return response()->json([
            'status'  => true,
            'message' => 'Data pengelolaan berhasil diperbarui.',
            'data'    => $data,
        ]);
    }

    #[OA\Delete(
        path: '/data-pengelolaan/{id}',
        summary: 'Hapus data pengelolaan sampah',
        description: 'Menghapus data pengelolaan secara permanen. Fasilitator hanya bisa hapus data miliknya sendiri.',
        tags: ['Data Pengelolaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data pengelolaan berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Data pengelolaan berhasil dihapus.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak'),
            new OA\Response(response: 404, description: 'Data pengelolaan tidak ditemukan'),
        ]
    )]

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataPengelolaanSampah::findOrFail($id);

        if (
            $user->hasRole('Fasilitator') && !$user->hasRole('Administrator')
            && $data->user_id !== $user->id
        ) {
            return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $data->delete();

        ActivityLog::log(
            'delete_pengelolaan',
            'Data pengelolaan sampah ID ' . $id . ' dihapus.',
            'DataPengelolaanSampah',
            $id
        );

        return response()->json([
            'status'  => true,
            'message' => 'Data pengelolaan berhasil dihapus.',
        ]);
    }
}