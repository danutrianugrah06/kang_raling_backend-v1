<?php

// app/Http/Controllers/Api/SwaggerDocs.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Kang Raling API Web Service",
    description: "Selamat datang di Dokumentasi Resmi Web Service **Kang Raling** (Kampung Ramah Lingkungan).\n\nAPI ini merupakan antarmuka integrasi data untuk Sistem Informasi Monitoring Sampah di lingkungan **Dinas Lingkungan Hidup (DLH) Kabupaten Garut**, Provinsi Jawa Barat.\n\n---\n\n## Panduan Autentikasi & Keamanan\n\nSebagian besar *endpoint* dalam API ini bersifat privat dan dienkripsi menggunakan metode otorisasi *HTTP Bearer Token*. Terdapat dua jenis token yang diizinkan oleh sistem:\n\n### 1. Internal Auth Token (Dashboard)\nDigunakan oleh pengguna sistem yang sah (Koordinator/Fasilitator) untuk mengelola data operasional. Token bersifat dinamis dan diperoleh dengan mengirimkan kredensial melalui *endpoint* `POST /api/v1/login`.\n\n### 2. External API Key (Interop)\nDigunakan secara eksklusif untuk komunikasi antar-server (machine-to-machine), seperti integrasi dengan platform provinsi. Token ini dilindungi oleh pembatasan *Rate Limit* (maksimal 60 *request*/menit) dan berstatus *read-only*. API Key dapat digenerate oleh Koordinator sistem pada menu pengembang.\n\n### Cara Melakukan Pengujian (Testing) API:\n1. Pastikan Anda telah memiliki salah satu token valid di atas.\n2. Klik tombol **Authorize** (ikon gembok hijau) di sudut kanan atas halaman ini.\n3. Masukkan token ke dalam form yang tersedia, lalu klik tombol **Authorize**.\n4. Sistem Swagger akan secara otomatis menyisipkan token tersebut ke setiap permintaan (request) yang Anda uji coba melalui tombol **Try it out**.",
    contact: new OA\Contact(
        name: "Developer Website Kang Raling",
        email: "danu3anugrah@gmail.com"
    ),
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Server Lokal Development"
)]
#[OA\Server(
    url: "https://kangraling.dlhgarut.id", 
    description: "Server Utama (Production Dinas Lingkungan Hidup)"
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
#[OA\Tag(name: "Manajemen User", description: "Kelola akun pengguna sistem. Khusus Koordinator dengan permission `manajemen.user`.")]
#[OA\Tag(name: "Role & Permission", description: "Kelola role dan permission sistem RBAC. Khusus Koordinator dengan permission `kelola.role-permission`.")]
#[OA\Tag(name: "API Key & Log", description: "Kelola API Key dan lihat log aktivitas sistem. Khusus Koordinator dengan permission `kelola.api-key`.")]
#[OA\Tag(name: "Developer Eksternal", description: "Generate dan kelola API token untuk integrasi dengan sistem eksternal. Membutuhkan Auth Token dengan permission `generate.api-token`.")]
#[OA\Tag(name: "Laporan", description: "Lihat dan cetak laporan data sampah dan pengelolaan. Membutuhkan Auth Token dengan permission `view.laporan` atau `cetak.laporan`.")]
#[OA\Tag(name: "Interop", description: "**Endpoint khusus untuk platform Sampah Kita Jabar (DLH Provinsi Jawa Barat).**\n\nMenggunakan **API Key Token** (bukan Auth Token biasa) dengan ability `sampah:read` atau `pengelolaan:read`.\n\nRate limit: **60 request per menit**.\n\nCara mendapatkan API Key: hubungi Koordinator sistem Kang Raling atau gunakan endpoint `POST /api/v1/developer/api-keys`.")]
class SwaggerDocs extends Controller {}
