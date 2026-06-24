<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    // =============================================
    // Helper: format data user untuk response
    // Dipanggil di login, me, updateProfile
    // =============================================
    private function formatUser(User $user): array
    {
        $user->load('roles.permissions');
        $allPermissions = $user->getAllPermissions()->pluck('name');

        return [
            'id'                     => $user->id,
            'nama'                   => $user->nama,
            'email'                  => $user->email,
            'roles'                  => $user->roles->map(fn($role) => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ]),
            'permissions'            => $allPermissions,
            'is_admin'               => $user->hasRole('Administrator'),
            'is_fasilitator'         => $user->hasRole('Fasilitator'),
            'is_pimpinan'            => $user->hasRole('Pimpinan'),
            'is_developer_eksternal' => $user->hasRole('Developer Eksternal'),
        ];
    }

    #[OA\Post(
        path: '/login',
        summary: 'Login ke dashboard',
        description: 'Melakukan autentikasi pengguna dan mengembalikan Bearer Token untuk mengakses semua endpoint protected. Token berlaku selama 7 hari.',
        tags: ['Auth & Akun'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@kangraling.id'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil.'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123xyz...'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama', type: 'string', example: 'Danu Tri Anugrah'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@kangraling.id'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
                                new OA\Property(property: 'is_admin', type: 'boolean', example: true),
                                new OA\Property(property: 'is_fasilitator', type: 'boolean', example: false),
                                new OA\Property(property: 'is_pimpinan', type: 'boolean', example: false),
                                new OA\Property(property: 'is_developer_eksternal', type: 'boolean', example: false),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Email atau password salah',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Email atau password salah.'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validasi gagal — field wajib tidak diisi'),
        ]
    )]

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        // Token login hanya punya ability dashboard:access
        // TIDAK BISA akses endpoint interop
        $token = $user->createToken(
            'auth_token',
            ['dashboard:access'],
            now()->addDays(7)
        )->plainTextToken;

        ActivityLog::log('login', 'User ' . $user->nama . ' berhasil login.');

        return response()->json([
            'status'  => true,
            'message' => 'Login berhasil.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ]);
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Logout dari dashboard',
        description: 'Menghapus token aktif pengguna yang sedang login. Token tidak bisa digunakan lagi setelah logout.',
        tags: ['Auth & Akun'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout berhasil.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid atau sudah expired'),
        ]
    )]

    public function logout(Request $request): JsonResponse
    {
        ActivityLog::log('logout', 'User ' . $request->user()->nama . ' logout.');
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    #[OA\Get(
        path: '/me',
        summary: 'Ambil data profil pengguna yang sedang login',
        description: 'Mengembalikan data lengkap pengguna yang sedang terautentikasi, termasuk semua role dan permission yang dimiliki.',
        tags: ['Auth & Akun'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data profil berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama', type: 'string', example: 'Danu Tri Anugrah'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@kangraling.id'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
                                new OA\Property(property: 'is_admin', type: 'boolean', example: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid atau tidak diberikan'),
        ]
    )]

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'user'   => $this->formatUser($request->user()),
        ]);
    }

    #[OA\Patch(
        path: '/me/update-profile',
        summary: 'Perbarui nama dan email profil',
        description: 'Mengupdate data nama dan email pengguna yang sedang login.',
        tags: ['Auth & Akun'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama', 'email'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string', example: 'Danu Tri Anugrah'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'danu@kangraling.id'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Profil berhasil diperbarui.'),
                        new OA\Property(property: 'user', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 422, description: 'Validasi gagal — email sudah dipakai akun lain'),
        ]
    )]

#[OA\Patch(
        path: '/me/update-password',
        summary: 'Perbarui password akun',
        description: 'Mengubah password pengguna yang sedang login. Wajib memasukkan password lama yang benar sebelum bisa ganti password baru.',
        tags: ['Auth & Akun'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password_lama', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'password_lama', type: 'string', format: 'password', example: 'passwordLama123'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'passwordBaru123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'passwordBaru123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password berhasil diperbarui',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Password berhasil diperbarui.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 422, description: 'Password lama salah atau password baru tidak memenuhi syarat'),
        ]
    )]

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'nama'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->nama  = $request->nama;
        $user->email = $request->email;
        $user->save();

        ActivityLog::log('update_profile', 'User ' . $user->nama . ' memperbarui data profil.');

        return response()->json([
            'status'  => true,
            'message' => 'Profil berhasil diperbarui.',
            'user'    => $this->formatUser($user),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'password_lama'     => 'required|string',
            'password'          => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Verifikasi password lama dulu sebelum ganti
        if (!Hash::check($request->password_lama, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Password lama yang Anda masukkan salah.',
                'errors'  => [
                    'password_lama' => ['Password lama tidak sesuai.']
                ],
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        ActivityLog::log('update_password', 'User ' . $user->nama . ' mengubah password akun.');

        return response()->json([
            'status'  => true,
            'message' => 'Password berhasil diperbarui.',
        ]);
    }
}
