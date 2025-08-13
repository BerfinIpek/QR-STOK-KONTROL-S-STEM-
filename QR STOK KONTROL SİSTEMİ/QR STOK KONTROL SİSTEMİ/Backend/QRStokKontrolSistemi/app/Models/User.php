<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'kullanicilar';

    protected $fillable = [
        'ad_soyad',
        'email',
        'sifre',
        'rol',
        'aktif_mi',
        'olusturma_tarihi',
        'guncelleme_tarihi',
    ];

    protected $hidden = [
        'sifre',
        'remember_token',
    ];

    public function getAuthPassword()
    {
        return $this->sifre;
    }
    public function getAuthPasswordName()
    {
        return 'sifre';
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'rol' => $this->rol,
            'ad_soyad' => $this->ad_soyad,
        ];
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'sifre' => 'hashed',
        ];
    }
}
