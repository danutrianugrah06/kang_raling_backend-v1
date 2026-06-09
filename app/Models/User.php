<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nama',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function artikels()
    {
        return $this->hasMany(Artikel::class);
    }

    public function galeris()
    {
        return $this->hasMany(Galeri::class);
    }

    public function edukasis()
    {
        return $this->hasMany(Edukasi::class);
    }

    public function dataSampahs()
    {
        return $this->hasMany(DataSampah::class);
    }

    public function dataPengelolaanSampahs()
    {
        return $this->hasMany(DataPengelolaanSampah::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class, 'generated_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'administrator';
    }

    public function isFasilitator(): bool
    {
        return $this->role === 'fasilitator';
    }
}