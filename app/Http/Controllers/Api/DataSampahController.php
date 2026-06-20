<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DataSampah;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DataSampahController extends Controller
{

#[OA\Get(
        path: '/api/v1/data-sampah',
        summary: 'Ambil semua data sampah (dashboard)',
        description: 'Mengembalikan daftar semua data sampah yang telah diinput. Mendukung filter dan pagination. Hanya untuk pengguna yang login dengan permission `input.data-sampah` atau `verifikasi.data-sampah`.',
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
            new OA\Parameter(name: 'status', in: 'query', required: false, description: 'Filter berdasarkan status: pending, verified, rejected', schema: new OA\Schema(type: 'string', enum: ['pending', 'verified', 'rejected'])),
            new OA\Parameter(name: 'desa_id', in: 'query', required: false, description: 'Filter berdasarkan ID desa', schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar data sampah berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission yang diperlukan'),
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = DataSampah::with([
            'desa',
            'user',
            'jenisSampah',
            'verifiedBy',
            'pengelolaans.jenisPengelolaan'
        ]);

        // Fasilitator hanya lihat data miliknya sendiri
        if ($user->hasRole('Fasilitator') && !$user->hasRole('Administrator')) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status'))        $query->where('status', $request->status);
        if ($request->filled('desa_id'))       $query->where('desa_id', $request->desa_id);
        if ($request->filled('tanggal_dari'))  $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->whereDate('tanggal', '<=', $request->tanggal_sampai);

        $data = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }

#[OA\Post(
        path: '/api/v1/data-sampah',
        summary: 'Input data sampah baru',
        description: 'Menambahkan data volume sampah baru dari sebuah desa. Data akan berstatus `pending` hingga diverifikasi oleh Administrator.',
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['desa_id', 'jenis_sampah_id', 'volume', 'tanggal'],
                properties: [
                    new OA\Property(property: 'desa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'jenis_sampah_id', type: 'integer', example: 2),
                    new OA\Property(property: 'volume', type: 'number', format: 'float', example: 125.5, description: 'Volume sampah dalam satuan kg'),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-06-16'),
                    new OA\Property(property: 'keterangan', type: 'string', example: 'Data sampah minggu pertama Juni', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Data sampah berhasil ditambahkan'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission input.data-sampah'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]

#[OA\Get(
        path: '/api/v1/data-sampah/publik',
        summary: 'Ambil data sampah publik',
        description: 'Mengembalikan data sampah yang sudah diverifikasi (`verified`) untuk ditampilkan di halaman publik website. Tidak membutuhkan token.',
        tags: ['Publik'],
        parameters: [
            new OA\Parameter(name: 'tahun', in: 'query', required: false, description: 'Filter berdasarkan tahun', schema: new OA\Schema(type: 'integer', example: 2026)),
            new OA\Parameter(name: 'desa_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data sampah publik berhasil diambil'),
        ]
    )]

    public function publik(Request $request): JsonResponse
    {
        $query = DataSampah::with(['desa', 'jenisSampah', 'pengelolaans'])
            ->where('status', 'verified');

        if ($request->filled('desa_id'))        $query->where('desa_id', $request->desa_id);
        if ($request->filled('tanggal_dari'))   $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->whereDate('tanggal', '<=', $request->tanggal_sampai);

        $data = $query->latest()->paginate(9999);

        return response()->json([
            'status' => true,
            'data'   => $data,
        ]);
    }

    #[OA\Put(
        path: '/api/v1/data-sampah/{id}',
        summary: 'Update data sampah',
        description: 'Mengupdate data sampah yang sudah ada. Hanya bisa dilakukan jika status masih `pending` atau `rejected`.',
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'desa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'jenis_sampah_id', type: 'integer', example: 2),
                    new OA\Property(property: 'volume', type: 'number', format: 'float', example: 130.0),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-06-16'),
                    new OA\Property(property: 'keterangan', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Data sampah berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission atau data sudah verified'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]

#[OA\Get(
        path: '/api/v1/data-sampah/statistik',
        summary: 'Ambil statistik data sampah publik',
        description: 'Mengembalikan statistik agregat data sampah untuk ditampilkan di halaman publik (grafik, chart, dll). Tidak membutuhkan token.',
        tags: ['Publik'],
        responses: [
            new OA\Response(response: 200, description: 'Statistik data sampah berhasil diambil'),
        ]
    )]

    public function statistik(): JsonResponse
    {
        $total = DataSampah::where('status', 'verified')->sum('jumlah');

        $perJenis = DataSampah::where('status', 'verified')
            ->with('jenisSampah')->get()
            ->groupBy('jenis_sampah_id')
            ->map(fn($items) => [
                'jenis' => $items->first()->jenisSampah->nama,
                'total' => $items->sum('jumlah'),
            ])->values();

        $perDesa = DataSampah::where('status', 'verified')
            ->with('desa')->get()
            ->groupBy('desa_id')
            ->map(fn($items) => [
                'desa'  => $items->first()->desa->nama_desa,
                'total' => $items->sum('jumlah'),
            ])->values();

        return response()->json([
            'status' => true,
            'data'   => [
                'total_sampah' => $total,
                'per_jenis'    => $perJenis,
                'per_desa'     => $perDesa,
            ],
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataSampah::with([
            'desa',
            'user',
            'jenisSampah',
            'verifiedBy',
            'pengelolaans.jenisPengelolaan'
        ])->findOrFail($id);

        if (
            $user->hasRole('Fasilitator') && !$user->hasRole('Administrator')
            && $data->user_id !== $user->id
        ) {
            return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'desa_id'         => 'required|exists:desas,id',
            'jenis_sampah_id' => 'required|exists:jenis_sampahs,id',
            'tanggal'         => 'required|date',
            'jumlah'          => 'required|numeric|min:0.01',
        ]);

        $data = DataSampah::create([
            'desa_id'         => $request->desa_id,
            'user_id'         => $request->user()->id,
            'jenis_sampah_id' => $request->jenis_sampah_id,
            'tanggal'         => $request->tanggal,
            'jumlah'          => $request->jumlah,
            'status'          => 'pending',
        ]);

        ActivityLog::log(
            'create_data_sampah',
            'Data sampah baru ditambahkan oleh ' . $request->user()->nama . '.',
            'DataSampah',
            $data->id
        );

        return response()->json([
            'status'  => true,
            'message' => 'Data sampah berhasil disimpan dan menunggu verifikasi.',
            'data'    => $data,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataSampah::findOrFail($id);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Administrator')) {
            if ($data->user_id !== $user->id) {
                return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
            }
            if ($data->isVerified()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data yang sudah diverifikasi tidak bisa diubah.',
                ], 422);
            }
        }

        // INI DIA YANG DIBENARKAN (Ubah integer jadi numeric)
        $request->validate([
            'desa_id'         => 'required|exists:desas,id',
            'jenis_sampah_id' => 'required|exists:jenis_sampahs,id',
            'tanggal'         => 'required|date',
            'jumlah'          => 'required|numeric|min:0.01',
        ]);

        $data->update([
            'desa_id'           => $request->desa_id,
            'jenis_sampah_id'   => $request->jenis_sampah_id,
            'tanggal'           => $request->tanggal,
            'jumlah'            => $request->jumlah,
            'status'            => 'pending',
            'catatan_penolakan' => null,
            'verified_by'       => null,
            'verified_at'       => null,
        ]);

        ActivityLog::log('update_data_sampah', 'Data sampah ID ' . $id . ' diperbarui.', 'DataSampah', $id);

        return response()->json([
            'status'  => true,
            'message' => 'Data sampah berhasil diperbarui dan menunggu verifikasi ulang.',
            'data'    => $data,
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/data-sampah/{id}',
        summary: 'Hapus data sampah',
        description: 'Menghapus data sampah secara permanen berdasarkan ID.',
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data sampah berhasil dihapus'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
        ]
    )]


    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataSampah::findOrFail($id);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Administrator')) {
            if ($data->user_id !== $user->id) {
                return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
            }
            if ($data->isVerified()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Data yang sudah diverifikasi tidak bisa dihapus.',
                ], 422);
            }
        }

        $data->delete();

        ActivityLog::log('delete_data_sampah', 'Data sampah ID ' . $id . ' dihapus.', 'DataSampah', $id);

        return response()->json([
            'status'  => true,
            'message' => 'Data sampah berhasil dihapus.',
        ]);
    }

#[OA\Post(
        path: '/api/v1/data-sampah/{id}/verify',
        summary: 'Verifikasi data sampah',
        description: 'Mengubah status data sampah dari `pending` menjadi `verified`. Khusus Administrator dengan permission `verifikasi.data-sampah`.',
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data sampah berhasil diverifikasi'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission verifikasi.data-sampah'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
        ]
    )]

    public function verify(Request $request, $id): JsonResponse
    {
        $data = DataSampah::findOrFail($id);

        if (!$data->isPending()) {
            return response()->json([
                'status'  => false,
                'message' => 'Hanya data dengan status pending yang bisa diverifikasi.',
            ], 422);
        }

        $data->update([
            'status'            => 'verified',
            'verified_by'       => $request->user()->id,
            'verified_at'       => now(),
            'catatan_penolakan' => null,
        ]);

        ActivityLog::log(
            'verify_data_sampah',
            'Data sampah ID ' . $id . ' diverifikasi oleh ' . $request->user()->nama . '.',
            'DataSampah',
            $id
        );

        return response()->json([
            'status'  => true,
            'message' => 'Data sampah berhasil diverifikasi.',
            'data'    => $data,
        ]);
    }

     #[OA\Post(
        path: '/api/v1/data-sampah/{id}/reject',
        summary: 'Tolak data sampah',
        description: 'Mengubah status data sampah dari `pending` menjadi `rejected`. Data yang ditolak bisa diedit ulang oleh Fasilitator dan akan kembali ke status `pending`.',
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'alasan', type: 'string', example: 'Volume tidak sesuai dengan laporan lapangan', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Data sampah berhasil ditolak'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission verifikasi.data-sampah'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
        ]
    )]

    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate([
            'catatan_penolakan' => 'required|string',
        ]);

        $data = DataSampah::findOrFail($id);

        if (!$data->isPending()) {
            return response()->json([
                'status'  => false,
                'message' => 'Hanya data dengan status pending yang bisa ditolak.',
            ], 422);
        }

        $data->update([
            'status'            => 'rejected',
            'verified_by'       => $request->user()->id,
            'verified_at'       => now(),
            'catatan_penolakan' => $request->catatan_penolakan,
        ]);

        ActivityLog::log(
            'reject_data_sampah',
            'Data sampah ID ' . $id . ' ditolak oleh ' . $request->user()->nama . '.',
            'DataSampah',
            $id
        );

        return response()->json([
            'status'  => true,
            'message' => 'Data sampah berhasil ditolak.',
            'data'    => $data,
        ]);
    }
}
