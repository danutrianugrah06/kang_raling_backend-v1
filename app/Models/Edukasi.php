<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Edukasi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'judul',
        'slug',
        'kategori',
        'deskripsi',
        'file_pdf',
        'link_video',
        'gambar',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($edukasi) {
            if (empty($edukasi->slug)) {
                $edukasi->slug = Str::slug($edukasi->judul);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}