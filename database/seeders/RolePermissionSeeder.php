<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =============================================
        // SEMUA PERMISSIONS
        // =============================================
        $permissions = [
            'input.data-sampah',
            'input.data-pengelolaan',
            'kelola.artikel',
            'kelola.galeri',
            'kelola.desa-binaan',
            'kelola.edukasi',
            'kelola.jenis-sampah',
            'kelola.jenis-pengelolaan',
            'verifikasi.data-sampah',
            'manajemen.user',
            'kelola.role-permission',
            'kelola.api-key',
            'kelola.tabel-generate',
            'view.laporan',
            'cetak.laporan',
            'generate.api-token',
            'kelola.pengaturan-akun',
            'view.preview-website',
            'view.hubungi-developer',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        // =============================================
        // ROLE: Koordinator
        // =============================================
        $roleAdmin = Role::firstOrCreate(['name' => 'Koordinator', 'guard_name' => 'web']);
        $roleAdmin->syncPermissions([
            'verifikasi.data-sampah',
            'manajemen.user',
            'kelola.role-permission',
            'kelola.api-key',
            'kelola.tabel-generate',
            'view.laporan',
            'cetak.laporan',
            'kelola.pengaturan-akun',
            'view.preview-website',
            'view.hubungi-developer',
        ]);

        // =============================================
        // ROLE: Fasilitator
        // =============================================
        $roleFasil = Role::firstOrCreate(['name' => 'Fasilitator', 'guard_name' => 'web']);
        $roleFasil->syncPermissions([
            'input.data-sampah',
            'input.data-pengelolaan',
            'kelola.artikel',
            'kelola.galeri',
            'kelola.desa-binaan',
            'kelola.edukasi',
            'kelola.jenis-sampah',
            'kelola.jenis-pengelolaan',
            'view.laporan',
            'cetak.laporan',
            'kelola.pengaturan-akun',
            'view.preview-website',
            'view.hubungi-developer',
        ]);

        // =============================================
        // ROLE: Pimpinan
        // =============================================
        $rolePimpinan = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web']);
        $rolePimpinan->syncPermissions([
            'view.laporan',
            'cetak.laporan',
            'kelola.pengaturan-akun',
            'view.preview-website',
            'view.hubungi-developer',
        ]);

        // =============================================
        // ROLE: Developer Eksternal
        // =============================================
        $roleDev = Role::firstOrCreate(['name' => 'Developer Eksternal', 'guard_name' => 'web']);
        $roleDev->syncPermissions([
            'generate.api-token',
            'kelola.tabel-generate',
            'kelola.pengaturan-akun',
            'view.preview-website',
            'view.hubungi-developer',
        ]);

        // =============================================
        // ASSIGN ROLES KE SEMUA USER
        // is_active sudah dihapus dari semua baris
        // =============================================
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@kangraling.id'],
            [
                'nama'     => 'Super Admin Kang Raling',
                'password' => Hash::make('admin123'),
            ]
        );
        $superAdmin->syncRoles(['Koordinator', 'Fasilitator']);

        $pimpinan = User::firstOrCreate(
            ['email' => 'pimpinan@kangraling.id'],
            [
                'nama'     => 'Kepala DLH Garut',
                'password' => Hash::make('pimpinan123'),
            ]
        );
        $pimpinan->syncRoles(['Pimpinan']);

        $devEksternal = User::firstOrCreate(
            ['email' => 'dev.sampahkita@kangraling.id'],
            [
                'nama'     => 'Developer Sampah Kita Jabar',
                'password' => Hash::make('developer123'),
            ]
        );
        $devEksternal->syncRoles(['Developer Eksternal']);

        foreach ([
            ['email' => 'fasilitator.cibunar@kangraling.id',    'nama' => 'Fasilitator Cibunar'],
            ['email' => 'fasilitator.citangtu@kangraling.id',   'nama' => 'Fasilitator Citangtu & Wanajaya'],
            ['email' => 'fasilitator.hegarmanah@kangraling.id', 'nama' => 'Fasilitator Hegarmanah'],
            ['email' => 'fasilitator.panembong@kangraling.id',  'nama' => 'Fasilitator Panembong'],
            ['email' => 'fasilitator.mekarjaya@kangraling.id',  'nama' => 'Fasilitator Mekarjaya'],
        ] as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'nama'     => $data['nama'],
                    'password' => Hash::make('fasilitator123'),
                ]
            );
            $user->syncRoles(['Fasilitator']);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}