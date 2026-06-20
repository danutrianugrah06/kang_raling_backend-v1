<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{

#[OA\Get(
        path: '/api/v1/users',
        summary: 'Ambil semua pengguna',
        description: 'Mengembalikan daftar semua pengguna sistem dengan role masing-masing. Mendukung pencarian dan filter berdasarkan role. Khusus Administrator.',
        tags: ['Manajemen User'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Cari berdasarkan nama atau email', schema: new OA\Schema(type: 'string', example: 'danu')),
            new OA\Parameter(name: 'role', in: 'query', required: false, description: 'Filter berdasarkan nama role', schema: new OA\Schema(type: 'string', example: 'Fasilitator')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar pengguna berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'nama', type: 'string', example: 'Danu Tri Anugrah'),
                                    new OA\Property(property: 'email', type: 'string', example: 'danu@kangraling.id'),
                                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'object')),
                                    new OA\Property(property: 'role_utama', type: 'string', example: 'Administrator'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'total', type: 'integer', example: 5),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission manajemen.user'),
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where('nama', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        $paginator = $users->through(fn($user) => [
            'id'         => $user->id,
            'nama'       => $user->nama,
            'email'      => $user->email,
            'roles'      => $user->roles->map(fn($r) => [
                'id'   => $r->id,
                'name' => $r->name,
            ]),
            'role_utama' => $user->roles->first()?->name ?? 'Tidak Ada Role',
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);

        $response = $paginator->toArray();
        $response['status'] = true;

        return response()->json($response);
    }

     #[OA\Get(
        path: '/api/v1/users/{id}',
        summary: 'Ambil detail pengguna',
        description: 'Mengembalikan detail lengkap satu pengguna beserta role dan permission-nya.',
        tags: ['Manajemen User'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID pengguna',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail pengguna berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama', type: 'string', example: 'Danu Tri Anugrah'),
                                new OA\Property(property: 'email', type: 'string', example: 'danu@kangraling.id'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'role_utama', type: 'string', example: 'Administrator'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission manajemen.user'),
            new OA\Response(response: 404, description: 'Pengguna tidak ditemukan'),
        ]
    )]

    public function show(int $id): JsonResponse
    {
        $user = User::with('roles.permissions')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => [
                'id'         => $user->id,
                'nama'       => $user->nama,
                'email'      => $user->email,
                'roles'      => $user->roles->map(fn($r) => [
                    'id'   => $r->id,
                    'name' => $r->name,
                ]),
                'role_utama' => $user->roles->first()?->name ?? 'Tidak Ada Role',
                'created_at' => $user->created_at,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/users',
        summary: 'Buat akun pengguna baru',
        description: 'Membuat akun pengguna baru dan langsung assign role. Satu akun bisa memiliki lebih dari satu role (Role Switcher). Khusus Administrator.',
        tags: ['Manajemen User'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama', 'email', 'password', 'roles'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string', example: 'Siti Rahayu'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'siti@kangraling.id'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123', description: 'Minimal 8 karakter'),
                    new OA\Property(
                        property: 'roles',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['Fasilitator'],
                        description: 'Array nama role. Bisa lebih dari satu: ["Administrator", "Fasilitator"]'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Akun berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: "Akun 'Siti Rahayu' berhasil dibuat."),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission manajemen.user'),
            new OA\Response(response: 422, description: 'Validasi gagal — email sudah dipakai atau role tidak valid'),
        ]
    )]

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'roles'    => 'required|array|min:1',
            'roles.*'  => 'string|exists:roles,name',
        ]);

        $user = User::create([
            'nama'     => $request->nama,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->syncRoles($request->roles);

        ActivityLog::log('create_user', "Admin membuat akun baru: {$user->nama}", 'User', $user->id);

        return response()->json([
            'status'  => true,
            'message' => "Akun '{$user->nama}' berhasil dibuat.",
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/users/{id}',
        summary: 'Update data pengguna',
        description: 'Mengupdate data pengguna. Semua field bersifat opsional (`sometimes`). Password hanya diupdate jika field `password` diisi. Role di-sync ulang jika field `roles` dikirim.',
        tags: ['Manajemen User'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 2)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nama', type: 'string', example: 'Siti Rahayu Updated', nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'siti.baru@kangraling.id', nullable: true),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'passwordBaru123', nullable: true, description: 'Kosongkan jika tidak ingin ganti password'),
                    new OA\Property(
                        property: 'roles',
                        type: 'array',
                        items: new OA\Items(type: 'string'),
                        example: ['Fasilitator', 'Pimpinan'],
                        nullable: true,
                        description: 'Kosongkan jika tidak ingin ubah role'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Akun berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: "Akun 'Siti Rahayu' berhasil diperbarui."),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission manajemen.user'),
            new OA\Response(response: 404, description: 'Pengguna tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nama'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:8',
            'roles'    => 'sometimes|array|min:1',
            'roles.*'  => 'string|exists:roles,name',
        ]);

        $user->update([
            'nama'  => $request->nama  ?? $user->nama,
            'email' => $request->email ?? $user->email,
            ...($request->filled('password')
                ? ['password' => Hash::make($request->password)]
                : []),
        ]);

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }

        ActivityLog::log('update_user', "Admin memperbarui akun: {$user->nama}", 'User', $user->id);

        return response()->json([
            'status'  => true,
            'message' => "Akun '{$user->nama}' berhasil diperbarui.",
        ]);
    }

     #[OA\Delete(
        path: '/api/v1/users/{id}',
        summary: 'Hapus pengguna',
        description: 'Menghapus akun pengguna secara permanen. Administrator tidak bisa menghapus akunnya sendiri.',
        tags: ['Manajemen User'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 2)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pengguna berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'User berhasil dihapus.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission manajemen.user'),
            new OA\Response(response: 404, description: 'Pengguna tidak ditemukan'),
            new OA\Response(response: 422, description: 'Tidak bisa hapus akun sendiri'),
        ]
    )]

    public function destroy(Request $request, $id): JsonResponse
    {
        if ($request->user()->id === (int) $id) {
            return response()->json([
                'status'  => false,
                'message' => 'Tidak bisa menghapus akun sendiri.',
            ], 422);
        }

        $user = User::findOrFail($id);
        $user->delete();

        ActivityLog::log('delete_user', "User {$user->nama} dihapus.", 'User', $id);

        return response()->json([
            'status'  => true,
            'message' => 'User berhasil dihapus.',
        ]);
    }
}