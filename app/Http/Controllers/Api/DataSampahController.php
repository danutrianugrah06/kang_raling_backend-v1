<?php

// app/Http/Controllers/Api/DataSampahController.php

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
        path: '/data-sampah',
        summary: 'Ambil semua data sampah (dashboard)',
        description: 'Mengembalikan daftar data sampah untuk dashboard internal. Fasilitator hanya melihat data miliknya sendiri, Koordinator melihat semua data. Mendukung filter status, desa, dan rentang tanggal.',
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, description: 'Filter berdasarkan status', schema: new OA\Schema(type: 'string', enum: ['pending', 'verified', 'rejected'])),
            new OA\Parameter(name: 'desa_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'tanggal_dari', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-30')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar data sampah berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', description: 'Hasil pagination Laravel berisi data sampah beserta relasi desa, user, jenisSampah, verifiedBy, dan pengelolaans'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = DataSampah::with(['desa', 'user', 'jenisSampah', 'verifiedBy', 'pengelolaans.jenisPengelolaan']);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Koordinator')) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status'))         $query->where('status', $request->status);
        if ($request->filled('desa_id'))        $query->where('desa_id', $request->desa_id);
        if ($request->filled('tanggal_dari'))   $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->whereDate('tanggal', '<=', $request->tanggal_sampai);

        $data = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json(['status' => true, 'data' => $data]);
    }

    #[OA\Get(
        path: '/data-sampah/publik',
        summary: 'Ambil data sampah publik',
        description: "Mengembalikan data sampah yang sudah berstatus `verified` DAN `is_public: true` untuk ditampilkan di halaman publik website. Tidak membutuhkan token.\n\nMendukung filter `bulan` dalam format nama Indonesia (contoh: `Juni`) maupun angka (`6`) — sistem otomatis menerjemahkan nama bulan ke angka.",
        tags: ['Publik'],
        parameters: [
            new OA\Parameter(name: 'desa_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'bulan', in: 'query', required: false, description: 'Nama bulan dalam Bahasa Indonesia (contoh: Juni) atau angka 1-12', schema: new OA\Schema(type: 'string', example: 'Juni')),
            new OA\Parameter(name: 'tahun', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2026)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data sampah publik berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', description: 'Hasil pagination berisi data sampah verified & publik, beserta relasi desa, jenisSampah, dan pengelolaans'),
                    ]
                )
            ),
        ]
    )]
    public function publik(Request $request): JsonResponse
    {
        $query = DataSampah::with(['desa', 'jenisSampah', 'pengelolaans'])
            ->where('status', 'verified')
            ->where('is_public', true);

        if ($request->filled('desa_id')) {
            $query->where('desa_id', $request->desa_id);
        }

        // --- PENERJEMAH NAMA BULAN KE ANGKA ---
        if ($request->filled('bulan')) {
            $bulan = $request->bulan;
            $bulanMap = [
                'Januari' => 1,
                'Februari' => 2,
                'Maret' => 3,
                'April' => 4,
                'Mei' => 5,
                'Juni' => 6,
                'Juli' => 7,
                'Agustus' => 8,
                'September' => 9,
                'Oktober' => 10,
                'November' => 11,
                'Desember' => 12
            ];

            // Jika dikirim huruf (contoh: "Juni"), ubah jadi angka 6
            if (!is_numeric($bulan) && isset($bulanMap[$bulan])) {
                $bulan = $bulanMap[$bulan];
            }
            $query->whereMonth('tanggal', $bulan);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }

        $data = $query->latest()->paginate(9999);

        return response()->json(['status' => true, 'data' => $data]);
    }

    #[OA\Get(
        path: '/data-sampah/statistik',
        summary: 'Ambil statistik data sampah publik',
        description: "Mengembalikan statistik agregat data sampah yang sudah `verified` dan `is_public: true` — total keseluruhan, total per jenis sampah, dan total per desa. Digunakan untuk grafik/chart di halaman publik. Mendukung filter yang sama dengan endpoint publik (bulan, tahun, desa).",
        tags: ['Publik'],
        parameters: [
            new OA\Parameter(name: 'desa_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'bulan', in: 'query', required: false, description: 'Nama bulan dalam Bahasa Indonesia (contoh: Juni) atau angka 1-12', schema: new OA\Schema(type: 'string', example: 'Juni')),
            new OA\Parameter(name: 'tahun', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2026)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistik data sampah berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_sampah', type: 'number', format: 'float', example: 1250.5),
                                new OA\Property(
                                    property: 'per_jenis',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'jenis', type: 'string', example: 'Sampah Organik'),
                                            new OA\Property(property: 'total', type: 'number', format: 'float', example: 500.0),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'per_desa',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'desa', type: 'string', example: 'Desa Sukamaju'),
                                            new OA\Property(property: 'total', type: 'number', format: 'float', example: 320.0),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function statistik(Request $request): JsonResponse
    {
        $query = DataSampah::where('status', 'verified')->where('is_public', true);

        if ($request->filled('desa_id')) {
            $query->where('desa_id', $request->desa_id);
        }

        // --- PENERJEMAH NAMA BULAN KE ANGKA ---
        if ($request->filled('bulan')) {
            $bulan = $request->bulan;
            $bulanMap = [
                'Januari' => 1,
                'Februari' => 2,
                'Maret' => 3,
                'April' => 4,
                'Mei' => 5,
                'Juni' => 6,
                'Juli' => 7,
                'Agustus' => 8,
                'September' => 9,
                'Oktober' => 10,
                'November' => 11,
                'Desember' => 12
            ];
            if (!is_numeric($bulan) && isset($bulanMap[$bulan])) {
                $bulan = $bulanMap[$bulan];
            }
            $query->whereMonth('tanggal', $bulan);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }

        $total = $query->sum('jumlah');

        $perJenis = (clone $query)->with('jenisSampah')->get()
            ->groupBy('jenis_sampah_id')
            ->map(fn($items) => [
                'jenis' => $items->first()->jenisSampah->nama ?? '-',
                'total' => $items->sum('jumlah'),
            ])->values();

        $perDesa = (clone $query)->with('desa')->get()
            ->groupBy('desa_id')
            ->map(fn($items) => [
                'desa'  => $items->first()->desa->nama_desa ?? '-',
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

    #[OA\Get(
        path: '/data-sampah/{id}',
        summary: 'Ambil detail satu data sampah',
        description: 'Mengembalikan detail lengkap satu data sampah beserta relasi desa, user, jenisSampah, verifiedBy, dan riwayat pengelolaannya. Fasilitator hanya bisa lihat data miliknya sendiri.',
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail data sampah berhasil diambil'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Fasilitator mencoba akses data milik pengguna lain'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
        ]
    )]
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataSampah::with(['desa', 'user', 'jenisSampah', 'verifiedBy', 'pengelolaans.jenisPengelolaan'])->findOrFail($id);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Koordinator') && $data->user_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
        }
        return response()->json(['status' => true, 'data' => $data]);
    }

    #[OA\Post(
        path: '/data-sampah',
        summary: 'Input data sampah baru',
        description: "Menambahkan data volume sampah baru. Data otomatis berstatus `pending` dan `is_public: false` sampai diverifikasi oleh Koordinator.",
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['desa_id', 'jenis_sampah_id', 'tanggal', 'jumlah'],
                properties: [
                    new OA\Property(property: 'desa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'jenis_sampah_id', type: 'integer', example: 2),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-06-16'),
                    new OA\Property(property: 'jumlah', type: 'number', format: 'float', example: 125.5, description: 'Jumlah sampah dalam Kg, minimal 0.01'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Data sampah berhasil disimpan'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
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
            'is_public'       => false, // Default awal pasti false (disembunyikan)
        ]);

        ActivityLog::log('create_data_sampah', 'Data sampah baru ditambahkan.', 'DataSampah', $data->id);
        return response()->json(['status' => true, 'message' => 'Data berhasil disimpan.', 'data' => $data], 201);
    }

    #[OA\Put(
        path: '/data-sampah/{id}',
        summary: 'Update data sampah',
        description: "Mengupdate data sampah. Setiap kali diupdate, status otomatis di-reset menjadi `pending` dan `is_public` kembali `false` — data harus diverifikasi ulang dari awal. Fasilitator tidak bisa update data yang sudah `verified`.",
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['desa_id', 'jenis_sampah_id', 'tanggal', 'jumlah'],
                properties: [
                    new OA\Property(property: 'desa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'jenis_sampah_id', type: 'integer', example: 2),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-06-16'),
                    new OA\Property(property: 'jumlah', type: 'number', format: 'float', example: 130.0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Data sampah berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak — bukan pemilik data'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal, atau data sudah verified (khusus Fasilitator)'),
        ]
    )]
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataSampah::findOrFail($id);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Koordinator')) {
            if ($data->user_id !== $user->id) return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
            if ($data->isVerified()) return response()->json(['status' => false, 'message' => 'Data verified tidak bisa diubah.'], 422);
        }

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
            'is_public'         => false,
            'catatan_penolakan' => null,
            'verified_by'       => null,
            'verified_at'       => null,
        ]);

        ActivityLog::log('update_data_sampah', 'Data sampah diperbarui.', 'DataSampah', $id);
        return response()->json(['status' => true, 'message' => 'Data berhasil diperbarui.', 'data' => $data]);
    }

    #[OA\Delete(
        path: '/data-sampah/{id}',
        summary: 'Hapus data sampah',
        description: 'Menghapus data sampah secara permanen. Fasilitator tidak bisa hapus data yang sudah berstatus `verified`.',
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data sampah berhasil dihapus'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Akses ditolak — bukan pemilik data'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
            new OA\Response(response: 422, description: 'Data sudah verified, tidak bisa dihapus (khusus Fasilitator)'),
        ]
    )]
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataSampah::findOrFail($id);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Koordinator')) {
            if ($data->user_id !== $user->id) return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
            if ($data->isVerified()) return response()->json(['status' => false, 'message' => 'Data verified tidak bisa dihapus.'], 422);
        }

        $data->delete();
        ActivityLog::log('delete_data_sampah', 'Data sampah dihapus.', 'DataSampah', $id);
        return response()->json(['status' => true, 'message' => 'Data berhasil dihapus.']);
    }

    #[OA\Post(
        path: '/data-sampah/{id}/verify',
        summary: 'Verifikasi data sampah',
        description: "Mengubah status data sampah dari `pending` menjadi `verified`. **Penting:** begitu diverifikasi, data otomatis menjadi `is_public: true` dan langsung tampil di halaman publik website — tidak ada langkah publikasi manual terpisah. Hanya data berstatus `pending` yang bisa diverifikasi.",
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data sampah berhasil diverifikasi & otomatis dipublikasikan'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
            new OA\Response(response: 422, description: 'Data bukan berstatus pending, tidak bisa diverifikasi'),
        ]
    )]
    public function verify(Request $request, $id): JsonResponse
    {
        $data = DataSampah::findOrFail($id);
        if (!$data->isPending()) return response()->json(['status' => false, 'message' => 'Hanya data pending yang bisa diverifikasi.'], 422);

        $data->update([
            'status'            => 'verified',
            'is_public'         => true, // <-- INI YANG BIKIN OTOMATIS PUBLIK!
            'verified_by'       => $request->user()->id,
            'verified_at'       => now(),
            'catatan_penolakan' => null,
        ]);

        ActivityLog::log('verify_data_sampah', 'Data diverifikasi & dipublikasikan.', 'DataSampah', $id);
        return response()->json(['status' => true, 'message' => 'Data diverifikasi & dipublikasikan.']);
    }

    #[OA\Post(
        path: '/data-sampah/{id}/reject',
        summary: 'Tolak data sampah',
        description: 'Mengubah status data sampah dari `pending` menjadi `rejected`. Wajib menyertakan catatan penolakan. Hanya data berstatus `pending` yang bisa ditolak.',
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['catatan_penolakan'],
                properties: [
                    new OA\Property(property: 'catatan_penolakan', type: 'string', example: 'Volume tidak sesuai dengan laporan lapangan'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Data sampah berhasil ditolak'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
            new OA\Response(response: 422, description: 'Catatan penolakan tidak diisi, atau data bukan berstatus pending'),
        ]
    )]
    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate(['catatan_penolakan' => 'required|string']);
        $data = DataSampah::findOrFail($id);
        if (!$data->isPending()) return response()->json(['status' => false, 'message' => 'Hanya data pending yang bisa ditolak.'], 422);

        $data->update([
            'status'            => 'rejected',
            'is_public'         => false,
            'verified_by'       => $request->user()->id,
            'verified_at'       => now(),
            'catatan_penolakan' => $request->catatan_penolakan,
        ]);

        ActivityLog::log('reject_data_sampah', 'Data ditolak.', 'DataSampah', $id);
        return response()->json(['status' => true, 'message' => 'Data berhasil ditolak.']);
    }

    #[OA\Post(
        path: '/data-sampah/{id}/cancel-verify',
        summary: 'Batalkan verifikasi data sampah',
        description: "Mengembalikan data sampah dari status `verified` kembali ke `pending`. Data otomatis ditarik dari publik (`is_public: false`). Hanya data berstatus `verified` yang bisa dibatalkan verifikasinya.",
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Verifikasi berhasil dibatalkan, data ditarik dari publik'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
            new OA\Response(response: 422, description: 'Data bukan berstatus verified, tidak bisa dibatalkan'),
        ]
    )]
    public function cancelVerify(Request $request, $id): JsonResponse
    {
        $data = DataSampah::findOrFail($id);
        if (!$data->isVerified()) return response()->json(['status' => false, 'message' => 'Hanya data terverifikasi yang bisa dibatalkan.'], 422);

        $data->update([
            'status'            => 'pending',
            'is_public'         => false, // Tarik turun dari publik
            'verified_by'       => null,
            'verified_at'       => null,
            'catatan_penolakan' => null,
        ]);

        ActivityLog::log('cancel_verify_sampah', 'Verifikasi dibatalkan.', 'DataSampah', $id);
        return response()->json(['status' => true, 'message' => 'Verifikasi dibatalkan.']);
    }

    #[OA\Post(
        path: '/data-sampah/{id}/toggle-publish',
        summary: 'Toggle status publikasi data sampah',
        description: "Membalik status `is_public` data sampah (true ↔ false) tanpa mengubah status verifikasinya. Berguna untuk menyembunyikan sementara data yang sudah `verified` dari halaman publik tanpa harus membatalkan verifikasinya. Hanya berlaku untuk data berstatus `verified`.",
        tags: ['Data Sampah'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Status publikasi berhasil diubah'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 404, description: 'Data sampah tidak ditemukan'),
            new OA\Response(response: 422, description: 'Data bukan berstatus verified, tidak bisa diubah status publikasinya'),
        ]
    )]
    public function togglePublish(Request $request, $id): JsonResponse
    {
        $data = DataSampah::findOrFail($id);
        if (!$data->isVerified()) return response()->json(['status' => false, 'message' => 'Hanya data terverifikasi yang bisa diubah publikasinya.'], 422);

        $data->update(['is_public' => !$data->is_public]);
        return response()->json(['status' => true, 'message' => 'Status publikasi diubah.']);
    }
}
