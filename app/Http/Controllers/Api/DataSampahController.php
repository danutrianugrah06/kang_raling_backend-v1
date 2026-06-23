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
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = DataSampah::with(['desa', 'user', 'jenisSampah', 'verifiedBy', 'pengelolaans.jenisPengelolaan']);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Administrator')) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status'))         $query->where('status', $request->status);
        if ($request->filled('desa_id'))        $query->where('desa_id', $request->desa_id);
        if ($request->filled('tanggal_dari'))   $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        if ($request->filled('tanggal_sampai')) $query->whereDate('tanggal', '<=', $request->tanggal_sampai);

        $data = $query->latest()->paginate($request->get('per_page', 10));

        return response()->json(['status' => true, 'data' => $data]);
    }

    // 🔥 PERBAIKAN: ENDPOINT PUBLIK SEKARANG BISA BACA FILTER BULAN & TAHUN
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
                'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4, 
                'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8, 
                'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
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
                'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4, 
                'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8, 
                'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12
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

    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataSampah::with(['desa', 'user', 'jenisSampah', 'verifiedBy', 'pengelolaans.jenisPengelolaan'])->findOrFail($id);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Administrator') && $data->user_id !== $user->id) {
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
            'is_public'       => false, // Default awal pasti false (disembunyikan)
        ]);

        ActivityLog::log('create_data_sampah', 'Data sampah baru ditambahkan.', 'DataSampah', $data->id);
        return response()->json(['status' => true, 'message' => 'Data berhasil disimpan.', 'data' => $data], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataSampah::findOrFail($id);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Administrator')) {
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

    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $data = DataSampah::findOrFail($id);

        if ($user->hasRole('Fasilitator') && !$user->hasRole('Administrator')) {
            if ($data->user_id !== $user->id) return response()->json(['status' => false, 'message' => 'Akses ditolak.'], 403);
            if ($data->isVerified()) return response()->json(['status' => false, 'message' => 'Data verified tidak bisa dihapus.'], 422);
        }

        $data->delete();
        ActivityLog::log('delete_data_sampah', 'Data sampah dihapus.', 'DataSampah', $id);
        return response()->json(['status' => true, 'message' => 'Data berhasil dihapus.']);
    }

    // 🔥 PERBAIKAN: SEKARANG VERIFIKASI LANGSUNG JADI PUBLIK (TRUE)
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

    public function togglePublish(Request $request, $id): JsonResponse
    {
        $data = DataSampah::findOrFail($id);
        if (!$data->isVerified()) return response()->json(['status' => false, 'message' => 'Hanya data terverifikasi yang bisa diubah publikasinya.'], 422);

        $data->update(['is_public' => !$data->is_public]);
        return response()->json(['status' => true, 'message' => 'Status publikasi diubah.']);
    }
}