<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Desa;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DesaController extends Controller
{
    public function index()
    {
        $desas = Desa::with(['profilTps'])->get();

        return response()->json(['data' => $desas]);
    }

    public function show($slug)
    {
        $desa = Desa::with(['profilTps'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $desa]);
    }

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

    public function destroy(Request $request, $id)
    {
        $desa = Desa::findOrFail($id);
        $desa->delete();

        ActivityLog::log('delete_desa', 'Desa ' . $desa->nama_desa . ' dihapus.', 'Desa', $id);

        return response()->json(['message' => 'Desa berhasil dihapus.']);
    }
}