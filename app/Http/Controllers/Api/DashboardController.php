<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataSampah;
use App\Models\Desa;
use App\Models\User;
use App\Models\Artikel;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalSampah      = DataSampah::where('status', 'verified')->sum('jumlah');
        $totalPending     = DataSampah::where('status', 'pending')->count();
        $totalDesa        = Desa::count();
        $totalFasilitator = User::where('role', 'fasilitator')->count();
        $totalArtikel     = Artikel::count();

        // Total per jenis sampah
        $perJenis = DataSampah::where('status', 'verified')
            ->with('jenisSampah')
            ->get()
            ->groupBy('jenis_sampah_id')
            ->map(function ($items) {
                return [
                    'jenis'  => $items->first()->jenisSampah->nama,
                    'total'  => $items->sum('jumlah'),
                ];
            })->values();

        // Data sampah pending untuk admin
        $pending = [];
        if ($request->user()->isAdmin()) {
            $pending = DataSampah::where('status', 'pending')
                ->with(['desa', 'user', 'jenisSampah'])
                ->latest()
                ->take(5)
                ->get();
        }

        return response()->json([
            'total_sampah'      => $totalSampah,
            'total_pending'     => $totalPending,
            'total_desa'        => $totalDesa,
            'total_fasilitator' => $totalFasilitator,
            'total_artikel'     => $totalArtikel,
            'per_jenis'         => $perJenis,
            'pending_terbaru'   => $pending,
        ]);
    }
}