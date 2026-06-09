<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DataPengelolaanSampah;
use App\Models\DataSampah;
use Illuminate\Http\Request;

class DataPengelolaanSampahController extends Controller
{
    public function index(Request $request)
    {
        $query = DataPengelolaanSampah::with(['dataSampah.desa', 'dataSampah.jenisSampah', 'jenisPengelolaan', 'user']);

        if ($request->user()->isFasilitator()) {
            $query->where('user_id', $request->user()->id);
        }

        $data = $query->latest()->paginate(10);

        return response()->json($data);
    }

    public function show(Request $request, $id)
    {
        $data = DataPengelolaanSampah::with(['dataSampah.desa', 'dataSampah.jenisSampah', 'jenisPengelolaan', 'user'])
            ->findOrFail($id);

        if ($request->user()->isFasilitator() && $data->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'data_sampah_id'       => 'required|exists:data_sampahs,id',
            'jenis_pengelolaan_id' => 'required|exists:jenis_pengelolaans,id',
            'jumlah'               => 'required|integer|min:1',
            'keterangan'           => 'nullable|string',
        ]);

        // Pastikan data sampah sudah verified
        $dataSampah = DataSampah::findOrFail($request->data_sampah_id);

        if (!$dataSampah->isVerified()) {
            return response()->json(['message' => 'Data pengelolaan hanya bisa diinput untuk data sampah yang sudah diverifikasi.'], 422);
        }

        // Fasilitator hanya bisa input pengelolaan untuk data sampah miliknya
        if ($request->user()->isFasilitator() && $dataSampah->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $data = DataPengelolaanSampah::create([
            'data_sampah_id'       => $request->data_sampah_id,
            'jenis_pengelolaan_id' => $request->jenis_pengelolaan_id,
            'user_id'              => $request->user()->id,
            'jumlah'               => $request->jumlah,
            'keterangan'           => $request->keterangan,
        ]);

        ActivityLog::log('create_pengelolaan', 'Data pengelolaan sampah ditambahkan.', 'DataPengelolaanSampah', $data->id);

        return response()->json(['message' => 'Data pengelolaan berhasil disimpan.', 'data' => $data], 201);
    }

    public function update(Request $request, $id)
    {
        $data = DataPengelolaanSampah::findOrFail($id);

        if ($request->user()->isFasilitator() && $data->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'jenis_pengelolaan_id' => 'required|exists:jenis_pengelolaans,id',
            'jumlah'               => 'required|integer|min:1',
            'keterangan'           => 'nullable|string',
        ]);

        $data->update($request->only('jenis_pengelolaan_id', 'jumlah', 'keterangan'));

        ActivityLog::log('update_pengelolaan', 'Data pengelolaan sampah ID ' . $id . ' diperbarui.', 'DataPengelolaanSampah', $id);

        return response()->json(['message' => 'Data pengelolaan berhasil diperbarui.', 'data' => $data]);
    }

    public function destroy(Request $request, $id)
    {
        $data = DataPengelolaanSampah::findOrFail($id);

        if ($request->user()->isFasilitator() && $data->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $data->delete();

        ActivityLog::log('delete_pengelolaan', 'Data pengelolaan sampah ID ' . $id . ' dihapus.', 'DataPengelolaanSampah', $id);

        return response()->json(['message' => 'Data pengelolaan berhasil dihapus.']);
    }
}