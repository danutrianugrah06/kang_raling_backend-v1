<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DataSampah;
use App\Models\DataPengelolaanSampah;

class InteropController extends Controller
{
    public function dataSampah()
    {
        // Hanya kirim data sampah yang sudah 'verified'
        $data = DataSampah::with(['desa', 'jenisSampah'])
            ->where('status', 'verified')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function dataPengelolaan()
    {
        $data = DataPengelolaanSampah::with(['dataSampah', 'jenisPengelolaan'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}