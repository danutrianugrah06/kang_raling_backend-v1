<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DesaController;
use App\Http\Controllers\Api\ProfilTpsController;
use App\Http\Controllers\Api\ArtikelController;
use App\Http\Controllers\Api\GaleriController;
use App\Http\Controllers\Api\EdukasiController;
use App\Http\Controllers\Api\JenisSampahController;
use App\Http\Controllers\Api\JenisPengelolaanController;
use App\Http\Controllers\Api\DataSampahController;
use App\Http\Controllers\Api\DataPengelolaanSampahController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\SinkronisasiLogController;
use App\Http\Controllers\Api\InteropController;
use App\Http\Controllers\Api\RolePermissionController;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PUBLIC ROUTES — Tanpa autentikasi, tanpa permission
    | Bisa diakses siapa saja termasuk masyarakat umum
    |--------------------------------------------------------------------------
    */
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/desas',              [DesaController::class, 'index']);
    Route::get('/desas/{slug}',       [DesaController::class, 'show']);

    Route::get('/artikels',           [ArtikelController::class, 'index']);
    Route::get('/artikels/{slug}',    [ArtikelController::class, 'show']);

    Route::get('/galeris',            [GaleriController::class, 'index']);
    Route::get('/galeris/{slug}',     [GaleriController::class, 'show']);

    Route::get('/edukasis',           [EdukasiController::class, 'index']);
    Route::get('/edukasis/{slug}',    [EdukasiController::class, 'show']);

    Route::get('/data-sampah/publik',     [DataSampahController::class, 'publik']);
    Route::get('/data-sampah/statistik',  [DataSampahController::class, 'statistik']);

    /*
    |--------------------------------------------------------------------------
    | PROTECTED ROUTES — Wajib login (Bearer Token Sanctum)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // --- Auth & Pengaturan Akun Dasar ---
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
        
        // Dashboard bisa diakses semua role yang sudah login
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Pengaturan akun sendiri — semua role yang login bisa akses
        Route::get('/me/profile',           [AuthController::class, 'me']);
        Route::patch('/me/update-profile',  [AuthController::class, 'updateProfile']);
        Route::patch('/me/update-password', [AuthController::class, 'updatePassword']);

        /*
        |--------------------------------------------------------------------------
        | DATA REFERENSI (DROPDOWN FORM)
        |--------------------------------------------------------------------------
        | Endpoint ini dibutuhkan oleh form input di frontend.
        | Semua user yang login (Fasilitator & Koordinator) diizinkan membaca data ini.
        */
        Route::get('/jenis-sampah',         [JenisSampahController::class, 'index']);
        Route::get('/jenis-pengelolaan',    [JenisPengelolaanController::class, 'index']);


        /*
        |--------------------------------------------------------------------------
        | AKSES BERSAMA (FASILITATOR & KOORDINATOR)
        |--------------------------------------------------------------------------
        | Menggunakan operator "|" (ATAU) agar rute GET tidak saling tumpang tindih.
        | Fasilitator butuh ini untuk melihat riwayat inputannya, 
        | Koordinator butuh ini untuk melihat data yang akan diverifikasi.
        */
        Route::middleware('permission:input.data-sampah|verifikasi.data-sampah')->group(function () {
            Route::get('/data-sampah',          [DataSampahController::class, 'index']);
            Route::get('/data-sampah/{id}',     [DataSampahController::class, 'show']);
        });

        Route::middleware('permission:input.data-pengelolaan|verifikasi.data-pengelolaan')->group(function () {
            Route::get('/data-pengelolaan',         [DataPengelolaanSampahController::class, 'index']);
            Route::get('/data-pengelolaan/{id}',    [DataPengelolaanSampahController::class, 'show']);
        });


        /*
        |--------------------------------------------------------------------------
        | FASILITATOR ROUTES (Input & Kelola Konten)
        |--------------------------------------------------------------------------
        | Berisi endpoint untuk Create, Update, dan Delete data.
        */
        // Input Data Sampah (Hanya CRUD)
        Route::middleware('permission:input.data-sampah')->group(function () {
            Route::post('/data-sampah',         [DataSampahController::class, 'store']);
            Route::put('/data-sampah/{id}',     [DataSampahController::class, 'update']);
            Route::delete('/data-sampah/{id}',  [DataSampahController::class, 'destroy']);
        });

        // Input Data Pengelolaan (Hanya CRUD)
        Route::middleware('permission:input.data-pengelolaan')->group(function () {
            Route::post('/data-pengelolaan',        [DataPengelolaanSampahController::class, 'store']);
            Route::put('/data-pengelolaan/{id}',    [DataPengelolaanSampahController::class, 'update']);
            Route::delete('/data-pengelolaan/{id}', [DataPengelolaanSampahController::class, 'destroy']);
        });

        // Kelola Artikel
        Route::middleware('permission:kelola.artikel')->group(function () {
            Route::post('/artikels',            [ArtikelController::class, 'store']);
            Route::get('/artikels/{id}/edit',   [ArtikelController::class, 'edit']);
            Route::put('/artikels/{id}',        [ArtikelController::class, 'update']);
            Route::delete('/artikels/{id}',     [ArtikelController::class, 'destroy']);
        });

        // Kelola Galeri
        Route::middleware('permission:kelola.galeri')->group(function () {
            Route::post('/galeris',             [GaleriController::class, 'store']);
            Route::get('/galeris/{id}/edit',    [GaleriController::class, 'edit']);
            Route::put('/galeris/{id}',         [GaleriController::class, 'update']);
            Route::delete('/galeris/{id}',      [GaleriController::class, 'destroy']);
        });

        // Kelola Desa Binaan & Profil TPS
        Route::middleware('permission:kelola.desa-binaan')->group(function () {
            Route::post('/desas',               [DesaController::class, 'store']);
            Route::get('/desas/{id}/edit',      [DesaController::class, 'edit']);
            Route::put('/desas/{id}',           [DesaController::class, 'update']);
            Route::delete('/desas/{id}',        [DesaController::class, 'destroy']);

            Route::get('/profil-tps',           [ProfilTpsController::class, 'index']);
            Route::post('/profil-tps',          [ProfilTpsController::class, 'store']);
            Route::get('/profil-tps/{id}',      [ProfilTpsController::class, 'show']);
            Route::put('/profil-tps/{id}',      [ProfilTpsController::class, 'update']);
            Route::delete('/profil-tps/{id}',   [ProfilTpsController::class, 'destroy']);
        });

        // Kelola Edukasi
        Route::middleware('permission:kelola.edukasi')->group(function () {
            Route::post('/edukasis',            [EdukasiController::class, 'store']);
            Route::get('/edukasis/{id}/edit',   [EdukasiController::class, 'edit']);
            Route::put('/edukasis/{id}',        [EdukasiController::class, 'update']);
            Route::delete('/edukasis/{id}',     [EdukasiController::class, 'destroy']);
        });

        // Kelola Jenis Sampah (Master Data)
        Route::middleware('permission:kelola.jenis-sampah')->group(function () {
            Route::post('/jenis-sampah',        [JenisSampahController::class, 'store']);
            Route::put('/jenis-sampah/{id}',    [JenisSampahController::class, 'update']);
            Route::delete('/jenis-sampah/{id}', [JenisSampahController::class, 'destroy']);
        });

        // Kelola Jenis Pengelolaan (Master Data)
        Route::middleware('permission:kelola.jenis-pengelolaan')->group(function () {
            Route::post('/jenis-pengelolaan',        [JenisPengelolaanController::class, 'store']);
            Route::put('/jenis-pengelolaan/{id}',    [JenisPengelolaanController::class, 'update']);
            Route::delete('/jenis-pengelolaan/{id}', [JenisPengelolaanController::class, 'destroy']);
        });


        /*
        |--------------------------------------------------------------------------
        | KOORDINATOR ROUTES (Verifikasi, Manajemen Sistem, Audit)
        |--------------------------------------------------------------------------
        | Semua akses penting dan vital (Persetujuan, Pembatalan, Publish) 
        | dikumpulkan dan dikunci rapat di dalam satu middleware ini.
        */
        // Aksi Verifikasi Data Sampah
        Route::middleware('permission:verifikasi.data-sampah')->group(function () {
            Route::post('/data-sampah/{id}/verify',        [DataSampahController::class, 'verify']);
            Route::post('/data-sampah/{id}/reject',        [DataSampahController::class, 'reject']);
            Route::post('/data-sampah/{id}/cancel-verify', [DataSampahController::class, 'cancelVerify']);
            Route::post('/data-sampah/{id}/toggle-publish',[DataSampahController::class, 'togglePublish']);
        });

        // Manajemen User
        Route::middleware('permission:manajemen.user')->group(function () {
            Route::get('/users',                      [UserController::class, 'index']);
            Route::post('/users',                     [UserController::class, 'store']);
            Route::get('/users/{id}',                 [UserController::class, 'show']);
            Route::put('/users/{id}',                 [UserController::class, 'update']);
            Route::delete('/users/{id}',              [UserController::class, 'destroy']);
        });

        // Kelola Role & Permission (Dynamic RBAC)
        Route::middleware('permission:kelola.role-permission')->group(function () {
            Route::get('/roles',                            [RolePermissionController::class, 'indexRoles']);
            Route::post('/roles',                           [RolePermissionController::class, 'storeRole']);
            Route::put('/roles/{id}',                       [RolePermissionController::class, 'updateRole']);
            Route::delete('/roles/{id}',                    [RolePermissionController::class, 'destroyRole']);

            Route::get('/permissions',                      [RolePermissionController::class, 'indexPermissions']);
            Route::post('/permissions',                     [RolePermissionController::class, 'storePermission']);
            Route::delete('/permissions/{id}',              [RolePermissionController::class, 'destroyPermission']);

            Route::post('/roles/{id}/sync-permissions',     [RolePermissionController::class, 'syncPermissions']);
            Route::post('/users/{id}/sync-roles',           [RolePermissionController::class, 'syncUserRoles']);
        });

        // Kelola API Key (BISA DIAKSES KOORDINATOR & DEVELOPER)
        Route::middleware('permission:kelola.api-key|generate.api-token')->group(function () {
            Route::get('/api-keys',                      [ApiKeyController::class, 'index']);
            Route::post('/api-keys',                     [ApiKeyController::class, 'generate']);
            Route::post('/api-keys/{id}/reset',          [ApiKeyController::class, 'reset']);
            Route::delete('/api-keys/{id}',              [ApiKeyController::class, 'destroy']);
            Route::patch('/api-keys/{id}/toggle-active', [ApiKeyController::class, 'toggleActive']);
        });

        // Kelola Log Sistem (KHUSUS KOORDINATOR)
        Route::middleware('permission:kelola.api-key')->group(function () {
            Route::get('/activity-logs',                 [ActivityLogController::class, 'index']);
            Route::get('/sinkronisasi-logs',             [SinkronisasiLogController::class, 'index']);
            Route::post('/sinkronisasi-logs/{id}/retry', [SinkronisasiLogController::class, 'retry']);
        });


        /*
        |--------------------------------------------------------------------------
        | PIMPINAN & LAPORAN ROUTES
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:view.laporan')->group(function () {
            Route::get('/laporan/data-sampah',      [DataSampahController::class, 'index']);
            Route::get('/laporan/data-pengelolaan', [DataPengelolaanSampahController::class, 'index']);
        });


        /*
        |--------------------------------------------------------------------------
        | DEVELOPER EKSTERNAL ROUTES (Manajemen Personal Access Token)
        |--------------------------------------------------------------------------
        | Di sinilah developer eksternal mengelola API Key milik mereka sendiri.
        */
        Route::middleware('permission:generate.api-token')->group(function () {
            Route::get('/developer/api-keys',         [ApiKeyController::class, 'index']);
            Route::post('/developer/api-keys',        [ApiKeyController::class, 'generate']); // bukan 'store'
            Route::delete('/developer/api-keys/{id}', [ApiKeyController::class, 'destroy']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | INTEROP ROUTES — Endpoint untuk ditarik oleh Aplikasi Eksternal
    |--------------------------------------------------------------------------
    | Dijaga oleh auth:sanctum PLUS ability check.
    | Hanya token yang dibuat dengan ability 'sampah:read' yang bisa masuk.
    | Token login biasa (auth_token) tidak punya ability ini → otomatis ditolak.
    */
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('interop')->group(function () {
        Route::get('/data-sampah', [InteropController::class, 'dataSampah'])
            ->middleware('abilities:sampah:read');

        Route::get('/data-pengelolaan', [InteropController::class, 'dataPengelolaan'])
            ->middleware('abilities:pengelolaan:read');
    });
});