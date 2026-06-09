<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisPengelolaan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public function dataPengelolaanSampahs()
    {
        return $this->hasMany(DataPengelolaanSampah::class);
    }
}