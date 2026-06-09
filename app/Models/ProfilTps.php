<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilTps extends Model
{
    use HasFactory;

    protected $table = 'profil_tps';

    protected $fillable = [
        'desa_id',
        'nama_tps',
        'nama_pengelola',
        'nama_fasilitator',
        'jumlah_warga_terlayani',
        'kegiatan_tps',
        'telepon',
        'gambar',
    ];

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }
}
