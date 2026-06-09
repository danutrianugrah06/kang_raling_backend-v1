<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SinkronisasiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_sampah_id',
        'status',
        'http_code',
        'response',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function dataSampah()
    {
        return $this->belongsTo(DataSampah::class);
    }
}