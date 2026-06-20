<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'nama',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // Helper methods via Spatie
    public function isAdmin(): bool
    {
        return $this->hasRole('Administrator');
    }

    public function isFasilitator(): bool
    {
        return $this->hasRole('Fasilitator');
    }

    public function isPimpinan(): bool
    {
        return $this->hasRole('Pimpinan');
    }

    public function isDeveloperEksternal(): bool
    {
        return $this->hasRole('Developer Eksternal');
    }

    // Relations
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
}