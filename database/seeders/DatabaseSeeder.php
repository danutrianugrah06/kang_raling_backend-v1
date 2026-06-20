<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Role dan Permission wajib jalan paling awal!
            RolePermissionSeeder::class,
            
            // 2. Master Data pendukung (tidak butuh relasi ke user)
            DesaSeeder::class,
            JenisSampahSeeder::class,
            JenisPengelolaanSeeder::class,
            
            // 3. User dijalankan TERAKHIR. 
            // Karena UserSeeder butuh tabel Role (dari Spatie) 
            // dan butuh tabel Desa (untuk profil Fasilitator)
            UserSeeder::class,
        ]);
    }
}