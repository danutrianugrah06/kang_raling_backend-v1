<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nama'      => 'Administrator',
            'email'     => 'admin@kangraling.id',
            'password'  => Hash::make('admin123'),
            'role'      => 'administrator',
            'is_active' => true,
        ]);

        $fasilitators = [
            ['nama' => 'Fasilitator Cibunar',           'email' => 'fasilitator.cibunar@kangraling.id'],
            ['nama' => 'Fasilitator Citangtu & Wanajaya', 'email' => 'fasilitator.citangtu@kangraling.id'],
            ['nama' => 'Fasilitator Hegarmanah',        'email' => 'fasilitator.hegarmanah@kangraling.id'],
            ['nama' => 'Fasilitator Panembong',         'email' => 'fasilitator.panembong@kangraling.id'],
            ['nama' => 'Fasilitator Mekarjaya',         'email' => 'fasilitator.mekarjaya@kangraling.id'],
        ];

        foreach ($fasilitators as $fasilitator) {
            User::create([
                'nama'      => $fasilitator['nama'],
                'email'     => $fasilitator['email'],
                'password'  => Hash::make('fasilitator123'),
                'role'      => 'fasilitator',
                'is_active' => true,
            ]);
        }
    }
}