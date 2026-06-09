<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApiKey;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json(['data' => ApiKey::with('generatedBy')->latest()->get()]);
    }

    public function generate(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $apiKey = ApiKey::create([
            'name'         => $request->name,
            'key'          => ApiKey::generateKey(),
            'is_active'    => true,
            'generated_by' => $request->user()->id,
        ]);

        ActivityLog::log('generate_api_key', 'API key ' . $apiKey->name . ' dibuat.', 'ApiKey', $apiKey->id);

        return response()->json(['message' => 'API key berhasil dibuat.', 'data' => $apiKey], 201);
    }

    public function reset(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $apiKey = ApiKey::findOrFail($id);
        $apiKey->update(['key' => ApiKey::generateKey()]);

        ActivityLog::log('reset_api_key', 'API key ' . $apiKey->name . ' direset.', 'ApiKey', $id);

        return response()->json(['message' => 'API key berhasil direset.', 'data' => $apiKey]);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $apiKey = ApiKey::findOrFail($id);
        $apiKey->delete();

        ActivityLog::log('delete_api_key', 'API key ' . $apiKey->name . ' dihapus.', 'ApiKey', $id);

        return response()->json(['message' => 'API key berhasil dihapus.']);
    }

    public function toggleActive(int $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        
        // Membalikkan nilai is_active (dari true ke false, atau sebaliknya)
        $apiKey->update([
            'is_active' => !$apiKey->is_active
        ]);

        return response()->json([
            'message' => 'Status API Key berhasil diperbarui.',
            'data' => $apiKey
        ]);
    }
}