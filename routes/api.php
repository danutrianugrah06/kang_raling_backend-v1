<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DesaController;
use App\Http\Controllers\Api\ProfilTpsController;
use App\Http\Controllers\Api\ArtikelController;
use App\Http\Controllers\Api\GaleriController;
use App\Http\Controllers\Api\EdukasiController;
use App\Http\Controllers\Api\JenisSampahController;
use App\Http\Controllers\Api\JenisPengelolaanController;
use App\Http\Controllers\Api\DataSampahController;
use App\Http\Controllers\Api\DataPengelolaanSampahController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InteropController;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ROUTES (Tidak butuh token apapun)
    |--------------------------------------------------------------------------
    */
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/desas', [DesaController::class, 'index']);
    Route::get('/desas/{slug}', [DesaController::class, 'show']);

    Route::get('/artikels', [ArtikelController::class, 'index']);
    Route::get('/artikels/{slug}', [ArtikelController::class, 'show']);

    Route::get('/galeris', [GaleriController::class, 'index']);
    Route::get('/galeris/{slug}', [GaleriController::class, 'show']);

    Route::get('/edukasis', [EdukasiController::class, 'index']);
    Route::get('/edukasis/{slug}', [EdukasiController::class, 'show']);

    // Data sampah publik
    Route::get('/data-sampah/publik', [DataSampahController::class, 'publik']);
    Route::get('/data-sampah/statistik', [DataSampahController::class, 'statistik']);


    /*
    |--------------------------------------------------------------------------
    | INTEROP ROUTES (Khusus Pihak Eksternal - Butuh API Key)
    |--------------------------------------------------------------------------
    */
    // Perhatikan: Ini DI LUAR auth:sanctum, hanya dijaga oleh api.key
    Route::middleware(['api.key', 'throttle:60,1'])->prefix('interop')->group(function () {
        Route::get('/data-sampah', [InteropController::class, 'dataSampah']);
        Route::get('/data-pengelolaan', [InteropController::class, 'dataPengelolaan']);
    });


    /*
    |--------------------------------------------------------------------------
    | PROTECTED ROUTES (Khusus Admin/Fasilitator - Butuh Token Login)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me/update-profile', [AuthController::class, 'updateProfile']);
        Route::patch('/me/update-password', [AuthController::class, 'updatePassword']);
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Artikel, Galeri, Edukasi, Desa, Profil TPS
        Route::post('/artikels', [ArtikelController::class, 'store']);
        Route::get('/artikels/{id}/edit', [ArtikelController::class, 'edit']);
        Route::put('/artikels/{id}', [ArtikelController::class, 'update']);
        Route::delete('/artikels/{id}', [ArtikelController::class, 'destroy']);
        
        Route::post('/galeris', [GaleriController::class, 'store']);
        Route::get('/galeris/{id}/edit', [GaleriController::class, 'edit']);
        Route::put('/galeris/{id}', [GaleriController::class, 'update']);
        Route::delete('/galeris/{id}', [GaleriController::class, 'destroy']);
        
        Route::post('/edukasis', [EdukasiController::class, 'store']);
        Route::get('/edukasis/{id}/edit', [EdukasiController::class, 'edit']);
        Route::put('/edukasis/{id}', [EdukasiController::class, 'update']);
        Route::delete('/edukasis/{id}', [EdukasiController::class, 'destroy']);
        
        Route::post('/desas', [DesaController::class, 'store']);
        Route::get('/desas/{id}/edit', [DesaController::class, 'edit']);
        Route::put('/desas/{id}', [DesaController::class, 'update']);
        Route::delete('/desas/{id}', [DesaController::class, 'destroy']);

        Route::get('/profil-tps', [ProfilTpsController::class, 'index']);
        Route::post('/profil-tps', [ProfilTpsController::class, 'store']);
        Route::get('/profil-tps/{id}', [ProfilTpsController::class, 'show']);
        Route::put('/profil-tps/{id}', [ProfilTpsController::class, 'update']);
        Route::delete('/profil-tps/{id}', [ProfilTpsController::class, 'destroy']);

        // Data Sampah & Verifikasi
        Route::get('/data-sampah', [DataSampahController::class, 'index']);
        Route::post('/data-sampah', [DataSampahController::class, 'store']);
        Route::get('/data-sampah/{id}', [DataSampahController::class, 'show']);
        Route::put('/data-sampah/{id}', [DataSampahController::class, 'update']);
        Route::delete('/data-sampah/{id}', [DataSampahController::class, 'destroy']);
        Route::post('/data-sampah/{id}/verify', [DataSampahController::class, 'verify']);
        Route::post('/data-sampah/{id}/reject', [DataSampahController::class, 'reject']);

        // Data Pengelolaan
        Route::get('/data-pengelolaan', [DataPengelolaanSampahController::class, 'index']);
        Route::post('/data-pengelolaan', [DataPengelolaanSampahController::class, 'store']);
        Route::get('/data-pengelolaan/{id}', [DataPengelolaanSampahController::class, 'show']);
        Route::put('/data-pengelolaan/{id}', [DataPengelolaanSampahController::class, 'update']);
        Route::delete('/data-pengelolaan/{id}', [DataPengelolaanSampahController::class, 'destroy']);

        // Jenis Sampah & Pengelolaan
        Route::get('/jenis-sampah', [JenisSampahController::class, 'index']);
        Route::post('/jenis-sampah', [JenisSampahController::class, 'store']);
        Route::put('/jenis-sampah/{id}', [JenisSampahController::class, 'update']);
        Route::delete('/jenis-sampah/{id}', [JenisSampahController::class, 'destroy']);

        Route::get('/jenis-pengelolaan', [JenisPengelolaanController::class, 'index']);
        Route::post('/jenis-pengelolaan', [JenisPengelolaanController::class, 'store']);
        Route::put('/jenis-pengelolaan/{id}', [JenisPengelolaanController::class, 'update']);
        Route::delete('/jenis-pengelolaan/{id}', [JenisPengelolaanController::class, 'destroy']);

        // Manajemen User
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);

        // Manajemen API Keys
        Route::get('/api-keys', [ApiKeyController::class, 'index']);
        Route::post('/api-keys', [ApiKeyController::class, 'generate']);
        Route::post('/api-keys/{id}/reset', [ApiKeyController::class, 'reset']);
        Route::delete('/api-keys/{id}', [ApiKeyController::class, 'destroy']);
        Route::patch('/api-keys/{id}/toggle-active', [ApiKeyController::class, 'toggleActive']);

        // Activity Log & Sinkronisasi
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/sinkronisasi-logs', [\App\Http\Controllers\Api\SinkronisasiLogController::class, 'index']);
        Route::post('/sinkronisasi-logs/{id}/retry', [\App\Http\Controllers\Api\SinkronisasiLogController::class, 'retry']);
    });
});