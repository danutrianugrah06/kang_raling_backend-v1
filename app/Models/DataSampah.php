<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSampah extends Model
{
    use HasFactory;

    protected $fillable = [
        'desa_id',
        'user_id',
        'jenis_sampah_id',
        'tanggal',
        'jumlah',
        'status',
        'is_public',
        'verified_by',
        'catatan_penolakan',
        'verified_at',
        'is_sent',
    ];

    protected $casts = [
        'tanggal'     => 'date',
        'verified_at' => 'datetime',
        'jumlah' => 'decimal:2',
    ];

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jenisSampah()
    {
        return $this->belongsTo(JenisSampah::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function pengelolaans()
    {
        return $this->hasMany(DataPengelolaanSampah::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function sinkronisasiLogs()
    {
        return $this->hasMany(SinkronisasiLog::class);
    }
}