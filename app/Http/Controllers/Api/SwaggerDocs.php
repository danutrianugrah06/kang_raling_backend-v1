<?php

// app/Http/Controllers/Api/SwaggerDocs.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Kang Raling API",
    description: "Dokumentasi resmi API sistem **Kang Raling** (Kampung Ramah Lingkungan).\n\nSistem Informasi Monitoring Sampah untuk **Dinas Lingkungan Hidup Kabupaten Garut**, Jawa Barat.\n\nDikembangkan oleh **Danu Tri Anugrah** (NIM 3202316006), D3 Teknik Informatika, Politeknik Negeri Pontianak.\n\n---\n\n## Cara Penggunaan\n\n### 1. Auth Token (Dashboard)\nDipakai untuk semua endpoint dashboard. Didapat dari endpoint `POST /api/v1/login`.\n\n### 2. API Key Token (Interop)\nDipakai khusus untuk endpoint `/api/v1/interop/`. Didapat dari endpoint `POST /api/v1/developer/api-keys`.\n\nSetelah mendapat token, klik tombol **Authorize** di kanan atas dan masukkan token dengan format:\n```\nBearer {token_anda}\n```",
    contact: new OA\Contact(
        name: "Danu Tri Anugrah",
        email: "danu@kangraling.id"
    ),
    license: new OA\License(
        name: "MIT",
        url: "https://opensource.org/licenses/MIT"
    )
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Server Lokal Development"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "Token",
    description: "Masukkan token dengan format: **Bearer {token}**"
)]
// ============================================================
// DEFINISI TAG — Urutan tag di sini = urutan tampil di UI
// ============================================================
#[OA\Tag(name: "Publik", description: "Endpoint publik tanpa autentikasi. Dapat diakses langsung tanpa token oleh siapapun.")]
#[OA\Tag(name: "Auth & Akun", description: "Login, logout, dan manajemen profil akun pengguna dashboard. Token didapat dari endpoint login di grup ini.")]
#[OA\Tag(name: "Dashboard", description: "Ringkasan statistik dan data untuk halaman utama dashboard. Membutuhkan Auth Token.")]
#[OA\Tag(name: "Data Sampah", description: "Input, kelola, dan verifikasi data volume sampah per desa. Membutuhkan Auth Token dengan permission `input.data-sampah` atau `verifikasi.data-sampah`.")]
#[OA\Tag(name: "Data Pengelolaan", description: "Input dan kelola data pengelolaan sampah (3R, kompos, dll). Membutuhkan Auth Token dengan permission `input.data-pengelolaan`.")]
#[OA\Tag(name: "Artikel", description: "Kelola konten artikel berita dan informasi lingkungan. Membutuhkan Auth Token dengan permission `kelola.artikel`.")]
#[OA\Tag(name: "Galeri", description: "Kelola galeri foto kegiatan lingkungan. Membutuhkan Auth Token dengan permission `kelola.galeri`.")]
#[OA\Tag(name: "Edukasi", description: "Kelola konten edukasi dan materi pembelajaran lingkungan. Membutuhkan Auth Token dengan permission `kelola.edukasi`.")]
#[OA\Tag(name: "Desa Binaan", description: "Kelola data desa binaan DLH Kabupaten Garut. Membutuhkan Auth Token dengan permission `kelola.desa-binaan`.")]
#[OA\Tag(name: "Profil TPS", description: "Kelola profil Tempat Penampungan Sampah per desa. Membutuhkan Auth Token dengan permission `kelola.desa-binaan`.")]
#[OA\Tag(name: "Master Data", description: "Kelola jenis sampah dan jenis pengelolaan sampah. Membutuhkan Auth Token dengan permission `kelola.jenis-sampah` atau `kelola.jenis-pengelolaan`.")]
#[OA\Tag(name: "Manajemen User", description: "Kelola akun pengguna sistem. Khusus Administrator dengan permission `manajemen.user`.")]
#[OA\Tag(name: "Role & Permission", description: "Kelola role dan permission sistem RBAC. Khusus Administrator dengan permission `kelola.role-permission`.")]
#[OA\Tag(name: "API Key & Log", description: "Kelola API Key dan lihat log aktivitas sistem. Khusus Administrator dengan permission `kelola.api-key`.")]
#[OA\Tag(name: "Developer Eksternal", description: "Generate dan kelola API token untuk integrasi dengan sistem eksternal. Membutuhkan Auth Token dengan permission `generate.api-token`.")]
#[OA\Tag(name: "Laporan", description: "Lihat dan cetak laporan data sampah dan pengelolaan. Membutuhkan Auth Token dengan permission `view.laporan` atau `cetak.laporan`.")]
#[OA\Tag(name: "Interop", description: "**Endpoint khusus untuk platform Sampah Kita Jabar (DLH Provinsi Jawa Barat).**\n\nMenggunakan **API Key Token** (bukan Auth Token biasa) dengan ability `sampah:read` atau `pengelolaan:read`.\n\nRate limit: **60 request per menit**.\n\nCara mendapatkan API Key: hubungi Administrator sistem Kang Raling atau gunakan endpoint `POST /api/v1/developer/api-keys`.")]
class SwaggerDocs extends Controller
{
    // ==========================================================
    // ENDPOINT DUMMY - JANGAN DIHAPUS SEBELUM ADA ENDPOINT ASLI
    // ==========================================================
    #[OA\Get(
        path: '/api/v1/ping',
        summary: 'Endpoint Tes (Dummy)',
        description: 'Endpoint ini hanya untuk memancing mesin Swagger agar berhasil di-generate karena standar OpenAPI mewajibkan minimal ada 1 Path/Endpoint.',
        tags: ['Publik'],
        responses: [
            new OA\Response(response: 200, description: 'Berhasil')
        ]
    )]
    public function ping()
    {
        // Fungsi ini kosong tidak apa-apa, hanya untuk pancingan awal
    }
}