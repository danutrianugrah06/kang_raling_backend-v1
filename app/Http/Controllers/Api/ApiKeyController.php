<?php

// app/Http/Controllers/Api/ApiKeyController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ApiKeyController extends Controller
{
    #[OA\Get(
        path: '/api/v1/api-keys',
        summary: 'Ambil semua API Key',
        description: 'Mengembalikan daftar API Key (token interop) yang sudah dibuat. Administrator melihat semua API Key dari semua developer eksternal, sedangkan Developer Eksternal hanya melihat API Key miliknya sendiri. Token login (`auth_token`) tidak ditampilkan di sini.',
        tags: ['API Key & Log'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar API Key berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 5),
                                    new OA\Property(property: 'name', type: 'string', example: 'sampah-kita-jabar-token'),
                                    new OA\Property(property: 'developer', type: 'string', example: 'Tim Sampah Kita Jabar'),
                                    new OA\Property(property: 'abilities', type: 'array', items: new OA\Items(type: 'string'), example: ['sampah:read', 'pengelolaan:read']),
                                    new OA\Property(property: 'last_used_at', type: 'string', format: 'date-time', nullable: true),
                                    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('Administrator')) {
            $tokens = \Laravel\Sanctum\PersonalAccessToken::with('tokenable')
                        ->where('name', '!=', 'auth_token')
                        ->orderBy('created_at', 'desc')->get();
        } else {
            $tokens = $user->tokens()
                        ->where('name', '!=', 'auth_token')
                        ->orderBy('created_at', 'desc')->get();
        }

        return response()->json([
            'status' => true,
            'data'   => $tokens->map(function ($token) {
                return [
                    'id'           => $token->id,
                    'name'         => $token->name,
                    'developer'    => $token->tokenable ? $token->tokenable->nama : 'Unknown',
                    'abilities'    => $token->abilities,
                    'last_used_at' => $token->last_used_at,
                    'expires_at'   => $token->expires_at,
                    'created_at'   => $token->created_at,
                    'token'        => '-',
                    'token_key'    => '-',
                    'is_active'    => true,
                ];
            })
        ]);
    }

    #[OA\Post(
        path: '/api/v1/developer/api-keys',
        summary: 'Generate API Key baru (Developer Eksternal)',
        description: "Membuat API Key baru untuk keperluan integrasi (interop). Token hanya memiliki ability `sampah:read` dan `pengelolaan:read` — TIDAK bisa mengakses endpoint dashboard.\n\n**Penting:** Token plaintext hanya ditampilkan SATU KALI saat dibuat. Simpan baik-baik, token tidak bisa dilihat ulang setelah response ini.\n\nMasa aktif token: **1 hari**, setelah itu harus generate ulang.",
        tags: ['Developer Eksternal'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'sampah-kita-jabar-token', description: 'Nama/label untuk token ini, agar mudah diidentifikasi'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'API Key berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'API Key berhasil dibuat. Salin token di bawah ini, token ini hanya akan muncul 1 kali!'),
                        new OA\Property(property: 'token', type: 'string', example: '3|aBcDeFgHiJkLmNoPqRsTuVwXyZ...'),
                        new OA\Property(property: 'data', type: 'object',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: '3|aBcDeFgHiJkLmNoPqRsTuVwXyZ...'),
                                new OA\Property(property: 'token_key', type: 'string', example: '3|aBcDeFgHiJkLmNoPqRsTuVwXyZ...'),
                                new OA\Property(property: 'name', type: 'string', example: 'sampah-kita-jabar-token'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission generate.api-token'),
            new OA\Response(response: 422, description: 'Validasi gagal — nama tidak diisi'),
        ]
    )]
    public function generate(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $abilities = ['sampah:read', 'pengelolaan:read'];
        $expiresAt = now()->addDay();

        $token = $user->createToken($request->name, $abilities, $expiresAt);

        return response()->json([
            'status'  => true,
            'message' => 'API Key berhasil dibuat. Salin token di bawah ini, token ini hanya akan muncul 1 kali!',
            'token'   => $token->plainTextToken,
            'data'    => [
                'token'     => $token->plainTextToken,
                'token_key' => $token->plainTextToken,
                'name'      => $request->name
            ]
        ], 201);
    }

    #[OA\Delete(
        path: '/api/v1/api-keys/{id}',
        summary: 'Hapus API Key',
        description: 'Mencabut/menghapus API Key secara permanen. Setelah dihapus, token tidak bisa lagi digunakan untuk mengakses endpoint interop. Administrator bisa hapus API Key milik siapapun, Developer Eksternal hanya bisa hapus miliknya sendiri.',
        tags: ['API Key & Log'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID token', schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API Key berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'API Key berhasil dihapus.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 404, description: 'API Key tidak ditemukan'),
        ]
    )]
    public function destroy(Request $request, $id): JsonResponse
    {
        if ($request->user()->hasRole('Administrator')) {
            $deleted = \Laravel\Sanctum\PersonalAccessToken::where('id', $id)->delete();
        } else {
            $deleted = $request->user()->tokens()->where('id', $id)->delete();
        }

        if (!$deleted) {
            return response()->json(['status' => false, 'message' => 'API Key tidak ditemukan.'], 404);
        }

        return response()->json(['status' => true, 'message' => 'API Key berhasil dihapus.']);
    }

    public function toggleActive(): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Status otomatis dikelola sistem (1 Hari kedaluwarsa).']);
    }

    public function reset(): JsonResponse
    {
        return response()->json(['status' => false, 'message' => 'Demi keamanan, hapus API Key ini dan buat token baru.'], 422);
    }
}