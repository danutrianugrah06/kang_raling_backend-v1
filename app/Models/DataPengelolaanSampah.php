<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataPengelolaanSampah extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_sampah_id',
        'jenis_pengelolaan_id',
        'user_id',
        'jumlah',
        'keterangan',
    ];

    public function dataSampah()
    {
        return $this->belongsTo(DataSampah::class);
    }

    public function jenisPengelolaan()
    {
        return $this->belongsTo(JenisPengelolaan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}