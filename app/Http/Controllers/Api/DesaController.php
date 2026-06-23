<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Desa;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class DesaController extends Controller
{

    #[OA\Get(
        path: '/api/v1/desas',
        summary: 'Ambil semua desa binaan',
        description: 'Mengembalikan daftar semua desa binaan DLH Kabupaten Garut beserta profil TPS-nya. Tidak membutuhkan token — endpoint publik.',
        tags: ['Publik'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar desa binaan berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'nama_desa', type: 'string', example: 'Desa Sukamaju'),
                                    new OA\Property(property: 'slug', type: 'string', example: 'desa-sukamaju'),
                                    new OA\Property(property: 'alamat', type: 'string', nullable: true, example: 'Jl. Raya Garut No. 1'),
                                    new OA\Property(property: 'profil_tps', type: 'array', items: new OA\Items(type: 'object')),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
        ]
    )]

    public function index()
    {
        $desas = Desa::with(['profilTps'])->latest()->paginate(9);

        return response()->json($desas);
    }

    #[OA\Get(
        path: '/api/v1/desas/{slug}',
        summary: 'Ambil detail desa binaan berdasarkan slug',
        description: 'Mengembalikan detail lengkap satu desa binaan beserta profil TPS-nya. Tidak membutuhkan token.',
        tags: ['Publik'],
        parameters: [
            new OA\Parameter(
                name: 'slug',
                in: 'path',
                required: true,
                description: 'Slug nama desa',
                schema: new OA\Schema(type: 'string', example: 'desa-sukamaju')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail desa berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama_desa', type: 'string', example: 'Desa Sukamaju'),
                                new OA\Property(property: 'slug', type: 'string', example: 'desa-sukamaju'),
                                new OA\Property(property: 'alamat', type: 'string', nullable: true),
                                new OA\Property(property: 'profil_tps', type: 'array', items: new OA\Items(type: 'object')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Desa tidak ditemukan'),
        ]
    )]

    public function show($slug)
    {
        $desa = Desa::with(['profilTps'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $desa]);
    }

    #[OA\Post(
        path: '/api/v1/desas',
        summary: 'Tambah desa binaan baru',
        description: 'Menambahkan desa binaan baru ke sistem. Membutuhkan Auth Token dengan permission `kelola.desa-binaan`.',
        tags: ['Desa Binaan'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama_desa'],
                properties: [
                    new OA\Property(property: 'nama_desa', type: 'string', example: 'Desa Sukamaju'),
                    new OA\Property(property: 'alamat', type: 'string', nullable: true, example: 'Jl. Raya Garut No. 1, Kecamatan Tarogong'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Desa berhasil ditambahkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Desa berhasil ditambahkan.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'nama_desa', type: 'string', example: 'Desa Sukamaju'),
                                new OA\Property(property: 'slug', type: 'string', example: 'desa-sukamaju'),
                                new OA\Property(property: 'alamat', type: 'string', nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.desa-binaan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]

    public function store(Request $request)
    {
        $request->validate([
            'nama_desa' => 'required|string|max:255',
            'alamat'    => 'nullable|string',
        ]);

        $slug = Str::slug($request->nama_desa);

        $originalSlug = $slug;
        $count = 1;
        while (Desa::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $desa = Desa::create([
            'nama_desa' => $request->nama_desa,
            'slug'      => $slug,
            'alamat'    => $request->alamat,
        ]);

        ActivityLog::log('create_desa', 'Desa ' . $desa->nama_desa . ' ditambahkan.', 'Desa', $desa->id);

        return response()->json(['message' => 'Desa berhasil ditambahkan.', 'data' => $desa], 201);
    }

    #[OA\Put(
        path: '/api/v1/desas/{id}',
        summary: 'Update data desa binaan',
        description: 'Mengupdate nama dan alamat desa binaan. Slug akan otomatis di-generate ulang dari nama desa yang baru.',
        tags: ['Desa Binaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID desa',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama_desa'],
                properties: [
                    new OA\Property(property: 'nama_desa', type: 'string', example: 'Desa Sukamaju Baru'),
                    new OA\Property(property: 'alamat', type: 'string', nullable: true, example: 'Jl. Raya Garut No. 2'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Desa berhasil diperbarui'),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.desa-binaan'),
            new OA\Response(response: 404, description: 'Desa tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]

    public function update(Request $request, $id)
    {
        $desa = Desa::findOrFail($id);

        $request->validate([
            'nama_desa' => 'required|string|max:255',
            'alamat'    => 'nullable|string',
        ]);

        $slug = Str::slug($request->nama_desa);

        $originalSlug = $slug;
        $count = 1;
        while (Desa::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $desa->update([
            'nama_desa' => $request->nama_desa,
            'slug'      => $slug,
            'alamat'    => $request->alamat,
        ]);

        ActivityLog::log('update_desa', 'Desa ' . $desa->nama_desa . ' diperbarui.', 'Desa', $desa->id);

        return response()->json(['message' => 'Desa berhasil diperbarui.', 'data' => $desa]);
    }

    #[OA\Delete(
        path: '/api/v1/desas/{id}',
        summary: 'Hapus desa binaan',
        description: 'Menghapus desa binaan secara permanen. Khusus Administrator dengan permission `kelola.desa-binaan`.',
        tags: ['Desa Binaan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Desa berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Desa berhasil dihapus.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Token tidak valid'),
            new OA\Response(response: 403, description: 'Tidak punya permission kelola.desa-binaan'),
            new OA\Response(response: 404, description: 'Desa tidak ditemukan'),
        ]
    )]

    public function destroy(Request $request, $id)
    {
        $desa = Desa::findOrFail($id);
        $desa->delete();

        ActivityLog::log('delete_desa', 'Desa ' . $desa->nama_desa . ' dihapus.', 'Desa', $id);

        return response()->json(['message' => 'Desa berhasil dihapus.']);
    }
}
