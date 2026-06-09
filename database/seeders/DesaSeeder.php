<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Desa;

class DesaSeeder extends Seeder
{
    public function run(): void
    {
        $desas = [
            ['nama_desa' => 'Cibunar',           'slug' => 'cibunar',            'alamat' => 'Kab. Garut, Jawa Barat'],
            ['nama_desa' => 'Citangtu Wanajaya', 'slug' => 'citangtu-wanajaya', 'alamat' => 'Kab. Garut, Jawa Barat'],
            ['nama_desa' => 'Hegarmanah',        'slug' => 'hegarmanah',         'alamat' => 'Kab. Garut, Jawa Barat'],
            ['nama_desa' => 'Panembong',         'slug' => 'panembong',          'alamat' => 'Kab. Garut, Jawa Barat'],
            ['nama_desa' => 'Mekarjaya',         'slug' => 'mekarjaya',          'alamat' => 'Kab. Garut, Jawa Barat'],
        ];

        foreach ($desas as $desa) {
            Desa::create($desa);
        }
    }
}
