<?php

namespace App\Services;

use App\Models\DataSampah;
use App\Models\SinkronisasiLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SampahKitaService
{
    protected ?string $apiUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.sampah_kita.url');
        $this->apiKey = config('services.sampah_kita.key');
    }

    public function kirimDataSampah(DataSampah $dataSampah): bool
    {
        if (!$this->apiUrl || !$this->apiKey) {
            SinkronisasiLog::create([
                'data_sampah_id' => $dataSampah->id,
                'status'         => 'failed',
                'http_code'      => null,
                'response'       => null,
                'error_message'  => 'API Key Platform Sampah Kita belum dikonfigurasi.',
                'sent_at'        => now(),
            ]);

            Log::warning('Sampah Kita API belum dikonfigurasi. Data sampah ID ' . $dataSampah->id . ' tidak terkirim.');

            return false;
        }

        $payload = [
            'desa'         => $dataSampah->desa->nama_desa,
            'jenis_sampah' => $dataSampah->jenisSampah->nama,
            'tanggal'      => $dataSampah->tanggal->format('Y-m-d'),
            'jumlah'       => $dataSampah->jumlah,
            'satuan'       => 'kg',
            'sumber'       => 'Kang Raling - DLH Kabupaten Garut',
        ];

        try {
            $response = Http::timeout(10)->withHeaders([
                'X-API-Key'    => $this->apiKey,
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/api/v1/data-sampah', $payload);

            $status = $response->successful() ? 'success' : 'failed';

            SinkronisasiLog::create([
                'data_sampah_id' => $dataSampah->id,
                'status'         => $status,
                'http_code'      => $response->status(),
                'response'       => $response->body(),
                'error_message'  => $response->successful() ? null : 'HTTP ' . $response->status(),
                'sent_at'        => now(),
            ]);

            if ($response->successful()) {
                $dataSampah->update(['is_sent' => true]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            SinkronisasiLog::create([
                'data_sampah_id' => $dataSampah->id,
                'status'         => 'failed',
                'http_code'      => null,
                'response'       => null,
                'error_message'  => $e->getMessage(),
                'sent_at'        => now(),
            ]);

            Log::error('Gagal kirim data ke Sampah Kita: ' . $e->getMessage());

            return false;
        }
    }
}