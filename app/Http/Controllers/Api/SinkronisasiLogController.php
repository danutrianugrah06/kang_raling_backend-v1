<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SinkronisasiLog;
use App\Services\SampahKitaService;
use Illuminate\Http\Request;

class SinkronisasiLogController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $query = SinkronisasiLog::with(['dataSampah.desa', 'dataSampah.jenisSampah'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    public function retry(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $log = SinkronisasiLog::with(['dataSampah.desa', 'dataSampah.jenisSampah'])
            ->findOrFail($id);

        if ($log->status === 'success') {
            return response()->json(['message' => 'Data ini sudah berhasil dikirim sebelumnya.'], 422);
        }

        $service = new SampahKitaService();
        $terkirim = $service->kirimDataSampah($log->dataSampah);

        return response()->json([
            'message' => $terkirim
                ? 'Berhasil dikirim ulang ke Platform Sampah Kita.'
                : 'Gagal dikirim ulang. API Key belum dikonfigurasi atau koneksi bermasalah.',
        ]);
    }
}