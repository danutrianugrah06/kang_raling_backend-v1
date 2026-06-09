<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model',
        'model_id',
        'deskripsi',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, ?string $deskripsi = null, ?string $model = null, ?int $modelId = null): void
    {
        static::create([
            'user_id'   => Auth::id(),
            'action'    => $action,
            'model'     => $model,
            'model_id'  => $modelId,
            'deskripsi' => $deskripsi,
            'ip_address'=> request()->ip(),
        ]);
    }
}