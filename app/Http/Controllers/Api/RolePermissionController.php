<?php

// app/Http/Controllers/Api/RolePermissionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use OpenApi\Attributes as OA;

class RolePermissionController extends Controller
{
    // =============================================
    // ROLES
    // =============================================

    #[OA\Get(
        path: '/api/v1/roles',
        summary: 'Ambil semua role',
        description: 'Mengembalikan daftar semua role beserta permission yang dimilikinya. Khusus Administrator dengan permission `kelola.role-permission`.',
        tags: ['Role & Permission'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar role berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Administrator'),
                                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['manajemen.user', 'verifikasi.data-sampah']),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.role-permission'),
        ]
    )]
    public function indexRoles(): JsonResponse
    {
        $roles = Role::with('permissions')->get()->map(fn($role) => [
            'id'          => $role->id,
            'name'        => $role->name,
            'permissions' => $role->permissions->pluck('name'),
            'created_at'  => $role->created_at,
        ]);

        return response()->json([
            'status' => true,
            'data'   => $roles,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/roles',
        summary: 'Tambah role baru',
        description: 'Membuat role baru dalam sistem RBAC. Nama role harus unik. Khusus Administrator.',
        tags: ['Role & Permission'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Supervisor Lapangan', description: 'Nama role baru, harus unik'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Role berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: "Role 'Supervisor Lapangan' berhasil dibuat."),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Administrator'),
            new OA\Response(response: 422, description: 'Validasi gagal — nama role sudah dipakai'),
        ]
    )]
    public function storeRole(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name|max:100',
        ]);

        $role = Role::create([
            'name'       => $request->name,
            'guard_name' => 'web',
        ]);

        return response()->json([
            'status'  => true,
            'message' => "Role '{$role->name}' berhasil dibuat.",
            'data'    => $role,
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/roles/{id}',
        summary: 'Update nama role',
        description: 'Mengubah nama role. Role sistem bawaan (`Administrator`, `Fasilitator`, `Pimpinan`, `Developer Eksternal`) tidak bisa diubah namanya untuk menjaga integritas RBAC.',
        tags: ['Role & Permission'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID role', schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Supervisor Lapangan (Revisi)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Nama role berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Mencoba mengubah role sistem (Administrator/Fasilitator/Pimpinan/Developer Eksternal)'),
            new OA\Response(response: 404, description: 'Role tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal — nama sudah dipakai role lain'),
        ]
    )]
    public function updateRole(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $protectedRoles = ['Administrator', 'Fasilitator', 'Pimpinan', 'Developer Eksternal'];
        if (in_array($role->name, $protectedRoles)) {
            return response()->json([
                'status'  => false,
                'message' => "Role '{$role->name}' adalah role sistem dan tidak bisa diubah namanya.",
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id . '|max:100',
        ]);

        $role->update(['name' => $request->name]);

        return response()->json([
            'status'  => true,
            'message' => 'Nama role berhasil diperbarui.',
            'data'    => $role,
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/roles/{id}',
        summary: 'Hapus role',
        description: 'Menghapus role dari sistem. Role `Administrator` dan `Fasilitator` tidak bisa dihapus demi keamanan sistem.',
        tags: ['Role & Permission'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Role berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Role berhasil dihapus.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Mencoba menghapus role Administrator atau Fasilitator'),
            new OA\Response(response: 404, description: 'Role tidak ditemukan'),
        ]
    )]
    public function destroyRole($id)
    {
        try {
            // 1. Cari Role berdasarkan ID
            $role = \Spatie\Permission\Models\Role::findOrFail($id);

            // 2. PROTEKSI: Jangan biarkan role dihapus kalau masih ada User yang memakainya!
            if ($role->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal: Masih ada user yang menggunakan role ini. Kosongkan dulu usernya.'
                ], 422); // Unprocessable Content
            }

            // 3. Hapus Role jika aman
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role berhasil dihapus.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus role: ' . $e->getMessage()
            ], 500);
        }
    }

    // =============================================
    // PERMISSIONS
    // =============================================

    #[OA\Get(
        path: '/api/v1/permissions',
        summary: 'Ambil semua permission',
        description: 'Mengembalikan daftar semua permission yang tersedia di sistem. Digunakan saat mengatur permission untuk suatu role.',
        tags: ['Role & Permission'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar permission berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'manajemen.user'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.role-permission'),
        ]
    )]
    public function indexPermissions(): JsonResponse
    {
        $permissions = Permission::all()->map(fn($p) => [
            'id'   => $p->id,
            'name' => $p->name,
        ]);

        return response()->json([
            'status' => true,
            'data'   => $permissions,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/permissions',
        summary: 'Tambah permission baru',
        description: 'Membuat permission baru dalam sistem. Nama permission harus unik, biasanya menggunakan format `kata.kata` (contoh: `kelola.artikel`).',
        tags: ['Role & Permission'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'export.laporan-excel', description: 'Nama permission baru, harus unik'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Permission berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: "Permission 'export.laporan-excel' berhasil dibuat."),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Administrator'),
            new OA\Response(response: 422, description: 'Validasi gagal — nama sudah dipakai'),
        ]
    )]
    public function storePermission(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name|max:100',
        ]);

        $permission = Permission::create([
            'name'       => $request->name,
            'guard_name' => 'web',
        ]);

        return response()->json([
            'status'  => true,
            'message' => "Permission '{$permission->name}' berhasil dibuat.",
            'data'    => $permission,
        ], 201);
    }

    #[OA\Delete(
        path: '/api/v1/permissions/{id}',
        summary: 'Hapus permission',
        description: 'Menghapus permission dari sistem secara permanen. Hati-hati — role yang memiliki permission ini akan otomatis kehilangan akses terkait.',
        tags: ['Role & Permission'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Permission berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: "Permission 'export.laporan-excel' berhasil dihapus."),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Administrator'),
            new OA\Response(response: 404, description: 'Permission tidak ditemukan'),
        ]
    )]
    public function destroyPermission(int $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'status'  => true,
            'message' => "Permission '{$permission->name}' berhasil dihapus.",
        ]);
    }

    // =============================================
    // SYNC
    // =============================================

    #[OA\Post(
        path: '/api/v1/roles/{id}/sync-permissions',
        summary: 'Sinkronisasi permission untuk suatu role',
        description: 'Mengatur ulang seluruh permission milik sebuah role. Permission lama yang tidak disertakan dalam request akan otomatis dihapus dari role tersebut (full replace, bukan tambah).',
        tags: ['Role & Permission'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID role', schema: new OA\Schema(type: 'integer', example: 2)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['permissions'],
                properties: [
                    new OA\Property(
                        property: 'permissions',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['input.data-sampah', 'kelola.artikel', 'kelola.galeri'],
                        description: 'Array nama permission. Daftar ini akan menggantikan seluruh permission role secara penuh.'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Permissions berhasil disinkronkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: "Permissions untuk role 'Fasilitator' berhasil diperbarui."),
                        new OA\Property(property: 'data', type: 'object',
                            properties: [
                                new OA\Property(property: 'role', type: 'string', example: 'Fasilitator'),
                                new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Administrator'),
            new OA\Response(response: 404, description: 'Role tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal — ada nama permission yang tidak terdaftar'),
        ]
    )]
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($request->permissions);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'status'  => true,
            'message' => "Permissions untuk role '{$role->name}' berhasil diperbarui.",
            'data'    => [
                'role'        => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/users/{id}/sync-roles',
        summary: 'Sinkronisasi role untuk suatu pengguna',
        description: 'Mengatur ulang seluruh role milik seorang pengguna. Mendukung multi-role (Role Switcher) — satu pengguna bisa memiliki lebih dari satu role sekaligus. Role lama yang tidak disertakan akan dihapus (full replace).',
        tags: ['Role & Permission'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID pengguna', schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['roles'],
                properties: [
                    new OA\Property(
                        property: 'roles',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['Fasilitator', 'Pimpinan'],
                        description: 'Array nama role. Bisa lebih dari satu untuk mendukung Role Switcher.'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Role pengguna berhasil disinkronkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: "Roles untuk user 'Siti Rahayu' berhasil diperbarui."),
                        new OA\Property(property: 'data', type: 'object',
                            properties: [
                                new OA\Property(property: 'user', type: 'string', example: 'Siti Rahayu'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Administrator'),
            new OA\Response(response: 404, description: 'Pengguna tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal — ada nama role yang tidak terdaftar'),
        ]
    )]
    public function syncUserRoles(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'roles'   => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user->syncRoles($request->roles);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'status'  => true,
            'message' => "Roles untuk user '{$user->nama}' berhasil diperbarui.",
            'data'    => [
                'user'  => $user->nama,
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }
}