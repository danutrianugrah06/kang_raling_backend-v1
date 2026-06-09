<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DataSampah;
use Illuminate\Http\Request;

class DataSampahController extends Controller
{
    // Untuk admin - lihat semua data
    public function index(Request $request)
    {
        $query = DataSampah::with(['desa', 'user', 'jenisSampah', 'verifiedBy', 'pengelolaans.jenisPengelolaan']);

        if ($request->user()->isFasilitator()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('desa_id')) {
            $query->where('desa_id', $request->desa_id);
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_sampai);
        }

        $data = $query->latest()->paginate(10);

        return response()->json($data);
    }

    // Untuk halaman publik - hanya verified
    public function publik(Request $request)
    {
        // TAMBAHKAN 'pengelolaans' DI DALAM ARRAY WITH
        $query = DataSampah::with(['desa', 'jenisSampah', 'pengelolaans'])
            ->where('status', 'verified');

        if ($request->filled('desa_id')) {
            $query->where('desa_id', $request->desa_id);
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_sampai);
        }

        $data = $query->latest()->paginate(9999);

        return response()->json($data);
    }

    // Statistik untuk halaman publik
    public function statistik()
    {
        $total = DataSampah::where('status', 'verified')->sum('jumlah');

        $perJenis = DataSampah::where('status', 'verified')
            ->with('jenisSampah')
            ->get()
            ->groupBy('jenis_sampah_id')
            ->map(function ($items) {
                return [
                    'jenis' => $items->first()->jenisSampah->nama,
                    'total' => $items->sum('jumlah'),
                ];
            })->values();

        $perDesa = DataSampah::where('status', 'verified')
            ->with('desa')
            ->get()
            ->groupBy('desa_id')
            ->map(function ($items) {
                return [
                    'desa'  => $items->first()->desa->nama_desa,
                    'total' => $items->sum('jumlah'),
                ];
            })->values();

        return response()->json([
            'total_sampah' => $total,
            'per_jenis'    => $perJenis,
            'per_desa'     => $perDesa,
        ]);
    }

    public function show(Request $request, $id)
    {
        $data = DataSampah::with(['desa', 'user', 'jenisSampah', 'verifiedBy', 'pengelolaans.jenisPengelolaan'])
            ->findOrFail($id);

        if ($request->user()->isFasilitator() && $data->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'desa_id'         => 'required|exists:desas,id',
            'jenis_sampah_id' => 'required|exists:jenis_sampahs,id',
            'tanggal'         => 'required|date',
            'jumlah'          => 'required|integer|min:1',
        ]);

        $data = DataSampah::create([
            'desa_id'         => $request->desa_id,
            'user_id'         => $request->user()->id,
            'jenis_sampah_id' => $request->jenis_sampah_id,
            'tanggal'         => $request->tanggal,
            'jumlah'          => $request->jumlah,
            'status'          => 'pending',
        ]);

        ActivityLog::log('create_data_sampah', 'Data sampah baru ditambahkan oleh ' . $request->user()->nama . '.', 'DataSampah', $data->id);

        return response()->json(['message' => 'Data sampah berhasil disimpan dan menunggu verifikasi.', 'data' => $data], 201);
    }

    public function update(Request $request, $id)
    {
        $data = DataSampah::findOrFail($id);

        // Fasilitator hanya bisa edit miliknya sendiri dan hanya jika pending atau rejected
        if ($request->user()->isFasilitator()) {
            if ($data->user_id !== $request->user()->id) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }

            if ($data->isVerified()) {
                return response()->json(['message' => 'Data yang sudah diverifikasi tidak bisa diubah.'], 422);
            }
        }

        $request->validate([
            'desa_id'         => 'required|exists:desas,id',
            'jenis_sampah_id' => 'required|exists:jenis_sampahs,id',
            'tanggal'         => 'required|date',
            'jumlah'          => 'required|integer|min:1',
        ]);

        // Kalau rejected lalu diedit, status kembali ke pending
        $data->update([
            'desa_id'            => $request->desa_id,
            'jenis_sampah_id'    => $request->jenis_sampah_id,
            'tanggal'            => $request->tanggal,
            'jumlah'             => $request->jumlah,
            'status'             => 'pending',
            'catatan_penolakan'  => null,
            'verified_by'        => null,
            'verified_at'        => null,
        ]);

        ActivityLog::log('update_data_sampah', 'Data sampah ID ' . $id . ' diperbarui.', 'DataSampah', $id);

        return response()->json(['message' => 'Data sampah berhasil diperbarui dan menunggu verifikasi ulang.', 'data' => $data]);
    }

    public function destroy(Request $request, $id)
    {
        $data = DataSampah::findOrFail($id);

        if ($request->user()->isFasilitator()) {
            if ($data->user_id !== $request->user()->id) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }

            if ($data->isVerified()) {
                return response()->json(['message' => 'Data yang sudah diverifikasi tidak bisa dihapus.'], 422);
            }
        }

        $data->delete();

        ActivityLog::log('delete_data_sampah', 'Data sampah ID ' . $id . ' dihapus.', 'DataSampah', $id);

        return response()->json(['message' => 'Data sampah berhasil dihapus.']);
    }

    // Verifikasi - khusus admin
    public function verify(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $data = DataSampah::findOrFail($id);

        if (!$data->isPending()) {
            return response()->json(['message' => 'Hanya data dengan status pending yang bisa diverifikasi.'], 422);
        }

        $data->update([
            'status'      => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'catatan_penolakan' => null,
        ]);

        ActivityLog::log('verify_data_sampah', 'Data sampah ID ' . $id . ' diverifikasi oleh ' . $request->user()->nama . '.', 'DataSampah', $id);

        return response()->json(['message' => 'Data sampah berhasil diverifikasi.', 'data' => $data]);
    }

    // Reject - khusus admin
    public function reject(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'catatan_penolakan' => 'required|string',
        ]);

        $data = DataSampah::findOrFail($id);

        if (!$data->isPending()) {
            return response()->json(['message' => 'Hanya data dengan status pending yang bisa ditolak.'], 422);
        }

        $data->update([
            'status'            => 'rejected',
            'verified_by'       => $request->user()->id,
            'verified_at'       => now(),
            'catatan_penolakan' => $request->catatan_penolakan,
        ]);

        ActivityLog::log('reject_data_sampah', 'Data sampah ID ' . $id . ' ditolak oleh ' . $request->user()->nama . '.', 'DataSampah', $id);

        return response()->json(['message' => 'Data sampah berhasil ditolak.', 'data' => $data]);
    }
}