<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisPengelolaan;

class JenisPengelolaanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nama'      => 'Dikompos',
                'deskripsi' => 'Sampah organik diolah menjadi kompos sebagai pupuk alami.',
            ],
            [
                'nama'      => 'Didaur Ulang',
                'deskripsi' => 'Sampah anorganik diolah kembali menjadi bahan baru.',
            ],
            [
                'nama'      => 'Dijual',
                'deskripsi' => 'Sampah yang masih bernilai ekonomi dijual ke pengepul.',
            ],
            [
                'nama'      => 'Digunakan Ulang',
                'deskripsi' => 'Sampah yang masih bisa digunakan kembali tanpa proses daur ulang.',
            ],
            [
                'nama'      => 'Dibuang ke TPA',
                'deskripsi' => 'Sampah residu yang tidak bisa diolah lebih lanjut.',
            ],
        ];

        foreach ($data as $item) {
            JenisPengelolaan::create($item);
        }
    }
}