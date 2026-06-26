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
        $user = $request->user();

        // 1. Siapkan Query Dasar untuk Data Sampah
        $querySampah = DataSampah::query();

        // Jika user adalah Fasilitator murni (bukan Koordinator), 
        // maka dia HANYA boleh melihat hitungan data sampahnya sendiri.
        if ($user->hasRole('Fasilitator') && !$user->hasRole('Koordinator')) {
            $querySampah->where('user_id', $user->id);
        }

        // 2. Hitung statistik sampah menggunakan clone agar query dasar tidak rusak
        $totalSampah  = (clone $querySampah)->sum('jumlah');
        $totalPending = (clone $querySampah)->where('status', 'pending')->count();
        $diterima     = (clone $querySampah)->where('status', 'verified')->count();
        $dikembalikan = (clone $querySampah)->where('status', 'rejected')->count();

        // 3. Hitung statistik entitas lain
        $totalDesa    = Desa::count();
        $totalArtikel = Artikel::count();
        
        // PERBAIKAN BUG 500: Menggunakan scope role() bawaan Spatie
        $totalFasilitator = User::role('Fasilitator')->count();

        // 4. Hitung Total per jenis sampah (Global, hanya yang verified)
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

        // 5. Data sampah pending terbaru (Khusus Admin)
        $pendingTerbaru = [];
        if ($user->hasRole('Koordinator')) {
            $pendingTerbaru = DataSampah::where('status', 'pending')
                ->with(['desa', 'user', 'jenisSampah'])
                ->latest()
                ->take(5)
                ->get();
        }

        // 6. Kembalikan semua data ke Vue.js
        return response()->json([
            'total_sampah'      => $totalSampah,
            'total_pending'     => $totalPending,
            'diterima'          => $diterima,       // Data Baru
            'dikembalikan'      => $dikembalikan,   // Data Baru
            'total_desa'        => $totalDesa,
            'total_fasilitator' => $totalFasilitator,
            'total_artikel'     => $totalArtikel,
            'per_jenis'         => $perJenis,
            'pending_terbaru'   => $pendingTerbaru,
        ]);
    }
}