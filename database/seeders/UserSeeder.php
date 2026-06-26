<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@kangraling.id'],
            [
                'nama'     => 'Koordinator',
                'password' => Hash::make('admin123'),
            ]
        );
        $admin->syncRoles(['Koordinator', 'Fasilitator']);

        // Fasilitator
        $fasilitators = [
            ['nama' => 'Fasilitator Cibunar',             'email' => 'fasilitator.cibunar@kangraling.id'],
            ['nama' => 'Fasilitator Citangtu & Wanajaya', 'email' => 'fasilitator.citangtu@kangraling.id'],
            ['nama' => 'Fasilitator Hegarmanah',          'email' => 'fasilitator.hegarmanah@kangraling.id'],
            ['nama' => 'Fasilitator Panembong',           'email' => 'fasilitator.panembong@kangraling.id'],
            ['nama' => 'Fasilitator Mekarjaya',           'email' => 'fasilitator.mekarjaya@kangraling.id'],
        ];

        foreach ($fasilitators as $fasilitator) {
            $user = User::updateOrCreate(
                ['email' => $fasilitator['email']],
                [
                    'nama'     => $fasilitator['nama'],
                    'password' => Hash::make('fasilitator123'),
                ]
            );
            $user->syncRoles(['Fasilitator']);
        }
    }
}