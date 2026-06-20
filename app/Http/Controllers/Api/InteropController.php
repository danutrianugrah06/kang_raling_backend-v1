<?php

// app/Http/Controllers/Api/InteropController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataSampah;
use App\Models\DataPengelolaanSampah;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class InteropController extends Controller
{
    #[OA\Get(
        path: '/api/v1/interop/data-sampah',
        summary: '[Sampah Kita Jabar] Ambil data sampah terverifikasi',
        description: "Endpoint khusus untuk diakses oleh Platform **Sampah Kita Jabar** (DLH Provinsi Jawa Barat).\n\nHanya mengembalikan data sampah yang sudah berstatus **verified**. Field response sudah difilter — hanya menampilkan data yang relevan untuk pihak ketiga, tidak ada data internal yang bocor.\n\n**Autentikasi:** Wajib menggunakan API Key Token dengan ability `sampah:read`.\n\n**Rate Limit:** 60 request per menit.",
        tags: ['Interop'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Jumlah data per halaman, maksimal 100', schema: new OA\Schema(type: 'integer', example: 50, maximum: 100)),
            new OA\Parameter(name: 'tanggal_dari', in: 'query', required: false, description: 'Filter tanggal mulai (format: YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', required: false, description: 'Filter tanggal akhir (format: YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-30')),
            new OA\Parameter(name: 'desa', in: 'query', required: false, description: 'Filter berdasarkan nama desa (pencarian parsial)', schema: new OA\Schema(type: 'string', example: 'Sukamaju')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data sampah berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'versi_api', type: 'string', example: '1.0'),
                                new OA\Property(property: 'sumber', type: 'string', example: 'Kang Raling - DLH Kabupaten Garut'),
                                new OA\Property(property: 'diambil_pada', type: 'string', format: 'date-time', example: '2026-06-17T10:30:00+07:00'),
                                new OA\Property(property: 'total_data', type: 'integer', example: 120),
                                new OA\Property(property: 'halaman', type: 'integer', example: 1),
                                new OA\Property(property: 'per_halaman', type: 'integer', example: 50),
                                new OA\Property(property: 'total_halaman', type: 'integer', example: 3),
                            ]
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 15),
                                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-06-10'),
                                    new OA\Property(property: 'desa', type: 'string', example: 'Desa Sukamaju'),
                                    new OA\Property(property: 'kabupaten', type: 'string', example: 'Garut'),
                                    new OA\Property(property: 'provinsi', type: 'string', example: 'Jawa Barat'),
                                    new OA\Property(property: 'jenis_sampah', type: 'string', example: 'Sampah Organik'),
                                    new OA\Property(property: 'jumlah', type: 'number', format: 'float', example: 125.5),
                                    new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
                                    new OA\Property(property: 'status', type: 'string', example: 'verified'),
                                    new OA\Property(property: 'diverifikasi_pada', type: 'string', format: 'date-time', nullable: true, example: '2026-06-11T08:00:00+07:00'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'API Key tidak valid, expired, atau tidak memiliki ability sampah:read'),
            new OA\Response(response: 429, description: 'Rate limit terlampaui — maksimal 60 request per menit'),
        ]
    )]
    public function dataSampah(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 50), 100);

        $query = DataSampah::with(['desa', 'jenisSampah'])
            ->where('status', 'verified')
            ->orderBy('tanggal', 'desc');

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_sampai);
        }
        if ($request->filled('desa')) {
            $query->whereHas('desa', function ($q) use ($request) {
                $q->where('nama_desa', 'like', '%' . $request->desa . '%');
            });
        }

        $paginated = $query->paginate($perPage);

        $data = collect($paginated->items())->map(fn($item) => [
            'id'               => $item->id,
            'tanggal'          => $item->tanggal->format('Y-m-d'),
            'desa'             => $item->desa->nama_desa,
            'kabupaten'        => 'Garut',
            'provinsi'         => 'Jawa Barat',
            'jenis_sampah'     => $item->jenisSampah->nama,
            'jumlah'           => $item->jumlah,
            'satuan'           => 'kg',
            'status'           => $item->status,
            'diverifikasi_pada'=> $item->verified_at?->toIso8601String(),
        ]);

        return response()->json([
            'status'  => true,
            'meta'    => [
                'versi_api'   => '1.0',
                'sumber'      => 'Kang Raling - DLH Kabupaten Garut',
                'diambil_pada'=> now()->toIso8601String(),
                'total_data'  => $paginated->total(),
                'halaman'     => $paginated->currentPage(),
                'per_halaman' => $paginated->perPage(),
                'total_halaman' => $paginated->lastPage(),
            ],
            'data' => $data,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/interop/data-pengelolaan',
        summary: '[Sampah Kita Jabar] Ambil data pengelolaan sampah',
        description: "Endpoint khusus untuk diakses oleh Platform **Sampah Kita Jabar** (DLH Provinsi Jawa Barat).\n\nHanya mengembalikan data pengelolaan yang sumber data sampahnya sudah **verified**.\n\n**Autentikasi:** Wajib menggunakan API Key Token dengan ability `pengelolaan:read`.\n\n**Rate Limit:** 60 request per menit.",
        tags: ['Interop'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, description: 'Jumlah data per halaman, maksimal 100', schema: new OA\Schema(type: 'integer', example: 50, maximum: 100)),
            new OA\Parameter(name: 'tanggal_dari', in: 'query', required: false, description: 'Filter tanggal mulai berdasarkan tanggal data sampah sumber', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', required: false, description: 'Filter tanggal akhir berdasarkan tanggal data sampah sumber', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-30')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data pengelolaan berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'versi_api', type: 'string', example: '1.0'),
                                new OA\Property(property: 'sumber', type: 'string', example: 'Kang Raling - DLH Kabupaten Garut'),
                                new OA\Property(property: 'diambil_pada', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'total_data', type: 'integer', example: 45),
                                new OA\Property(property: 'halaman', type: 'integer', example: 1),
                                new OA\Property(property: 'per_halaman', type: 'integer', example: 50),
                                new OA\Property(property: 'total_halaman', type: 'integer', example: 1),
                            ]
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 8),
                                    new OA\Property(property: 'data_sampah_id', type: 'integer', example: 15),
                                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-06-10'),
                                    new OA\Property(property: 'desa', type: 'string', example: 'Desa Sukamaju'),
                                    new OA\Property(property: 'kabupaten', type: 'string', example: 'Garut'),
                                    new OA\Property(property: 'provinsi', type: 'string', example: 'Jawa Barat'),
                                    new OA\Property(property: 'jenis_sampah', type: 'string', example: 'Sampah Organik'),
                                    new OA\Property(property: 'jenis_pengelolaan', type: 'string', example: 'Kompos'),
                                    new OA\Property(property: 'jumlah', type: 'number', format: 'float', example: 50.5),
                                    new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
                                    new OA\Property(property: 'keterangan', type: 'string', nullable: true, example: 'Kompos dari sampah organik RT 01'),
                                    new OA\Property(property: 'dicatat_pada', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'API Key tidak valid, expired, atau tidak memiliki ability pengelolaan:read'),
            new OA\Response(response: 429, description: 'Rate limit terlampaui — maksimal 60 request per menit'),
        ]
    )]
    public function dataPengelolaan(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 50), 100);

        $query = DataPengelolaanSampah::with([
                'dataSampah.desa',
                'dataSampah.jenisSampah',
                'jenisPengelolaan'
            ])
            ->whereHas('dataSampah', fn($q) => $q->where('status', 'verified'))
            ->orderBy('created_at', 'desc');

        if ($request->filled('tanggal_dari')) {
            $query->whereHas('dataSampah', function ($q) use ($request) {
                $q->whereDate('tanggal', '>=', $request->tanggal_dari);
            });
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereHas('dataSampah', function ($q) use ($request) {
                $q->whereDate('tanggal', '<=', $request->tanggal_sampai);
            });
        }

        $paginated = $query->paginate($perPage);

        $data = collect($paginated->items())->map(fn($item) => [
            'id'                => $item->id,
            'data_sampah_id'    => $item->data_sampah_id,
            'tanggal'           => $item->dataSampah->tanggal->format('Y-m-d'),
            'desa'              => $item->dataSampah->desa->nama_desa,
            'kabupaten'         => 'Garut',
            'provinsi'          => 'Jawa Barat',
            'jenis_sampah'      => $item->dataSampah->jenisSampah->nama,
            'jenis_pengelolaan' => $item->jenisPengelolaan->nama,
            'jumlah'            => $item->jumlah,
            'satuan'            => 'kg',
            'keterangan'        => $item->keterangan,
            'dicatat_pada'      => $item->created_at->toIso8601String(),
        ]);

        return response()->json([
            'status'  => true,
            'meta'    => [
                'versi_api'     => '1.0',
                'sumber'        => 'Kang Raling - DLH Kabupaten Garut',
                'diambil_pada'  => now()->toIso8601String(),
                'total_data'    => $paginated->total(),
                'halaman'       => $paginated->currentPage(),
                'per_halaman'   => $paginated->perPage(),
                'total_halaman' => $paginated->lastPage(),
            ],
            'data' => $data,
        ]);
    }
}