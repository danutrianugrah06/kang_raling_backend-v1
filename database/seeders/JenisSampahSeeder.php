<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisSampah;

class JenisSampahSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nama'      => 'Organik',
                'deskripsi' => 'Sampah yang berasal dari makhluk hidup dan mudah terurai seperti sisa makanan, daun, dan ranting.',
            ],
            [
                'nama'      => 'Anorganik',
                'deskripsi' => 'Sampah yang tidak mudah terurai seperti plastik, kertas, kaca, dan logam.',
            ],
            [
                'nama'      => 'Residu',
                'deskripsi' => 'Sampah yang tidak dapat didaur ulang maupun dikompos sehingga harus dibuang ke TPA.',
            ],
        ];

        foreach ($data as $item) {
            JenisSampah::create($item);
        }
    }
}