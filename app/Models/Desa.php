<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Desa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_desa',
        'slug',
        'alamat',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($desa) {
            if (empty($desa->slug)) {
                $desa->slug = Str::slug($desa->nama_desa);
            }
        });
    }

    public function profilTps()
    {
        return $this->hasMany(ProfilTps::class);
    }

    public function dataSampahs()
    {
        return $this->hasMany(DataSampah::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}