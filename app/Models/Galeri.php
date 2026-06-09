<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Galeri extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gambar',
        'slug',
        'keterangan',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($galeri) {
            if (empty($galeri->slug)) {
                $galeri->slug = Str::slug($galeri->keterangan ?? 'galeri-' . now()->timestamp);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}