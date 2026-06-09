<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-API-Key');

        // 1. Cek apakah Header X-API-Key dikirim
        if (!$key) {
            return response()->json(['message' => 'Unauthorized. API Key is missing.'], 401);
        }

        // 2. Cek di database: Apakah Key cocok DAN statusnya aktif?
        // Catatan: Jika model ApiKey menggunakan SoftDeletes, Laravel otomatis mengabaikan data yang sudah dihapus.
        $apiKey = ApiKey::where('key', $key)
                        ->where('is_active', true)
                        ->first();

        // 3. Jika token tidak ada, sudah dihapus, atau sedang dinonaktifkan -> TOLAK!
        if (!$apiKey) {
            return response()->json(['message' => 'Unauthorized. API Key is invalid, inactive, or deleted.'], 401);
        }

        // 4. Update waktu terakhir digunakan (last_used_at)
        $apiKey->update(['last_used_at' => now()]);

        return $next($request);
    }
}