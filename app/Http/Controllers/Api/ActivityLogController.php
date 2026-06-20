<?php

// app/Http/Controllers/Api/ActivityLogController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ActivityLogController extends Controller
{
    #[OA\Get(
        path: '/api/v1/activity-logs',
        summary: 'Ambil log aktivitas sistem',
        description: 'Mengembalikan riwayat aktivitas yang tercatat di sistem (login, logout, create, update, delete, dll) beserta pengguna yang melakukannya. Mendukung filter berdasarkan jenis aksi. Khusus Administrator.',
        tags: ['API Key & Log'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'action',
                in: 'query',
                required: false,
                description: 'Filter berdasarkan jenis aksi, contoh: login, create_artikel, delete_user',
                schema: new OA\Schema(type: 'string', example: 'login')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar log aktivitas berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'action', type: 'string', example: 'login'),
                                    new OA\Property(property: 'description', type: 'string', example: 'User Danu Tri Anugrah berhasil login.'),
                                    new OA\Property(property: 'user', type: 'object'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'total', type: 'integer', example: 150),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Bukan Administrator'),
        ]
    )]
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $query = ActivityLog::with('user')->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $data = $query->paginate(20);

        return response()->json($data);
    }
}