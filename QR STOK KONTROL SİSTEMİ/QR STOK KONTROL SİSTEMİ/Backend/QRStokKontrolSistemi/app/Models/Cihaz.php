<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cihaz extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'cihazlar';

    protected $fillable = [
        'cihaz_id',
        'cihaz_adi',
        'aciklama',
        'kategori',
        'marka',
        'model',
        'seri_no',
        'qr_degeri',
        'durum',
        'konum',
        'ekleyen_kullanici',
    ];

    protected $casts = [
        'olusturma_tarihi' => 'datetime',
        'guncelleme_tarihi' => 'datetime',
    ];

    // Cihazı ekleyen kullanıcı ilişkisi
    public function ekleyen()
    {
        return $this->belongsTo(User::class, 'ekleyen_kullanici');
    }


    // Benzersiz cihaz ID oluşturma
    public static function generateUniqueId()
    {
        do {
            $id = 'CHZ-' . strtoupper(uniqid());
        } while (self::where('cihaz_id', $id)->exists());
        
        return $id;
    }

    // Benzersiz QR değeri oluşturma
    public static function generateQrValue()
    {
        do {
            $qr = 'QR-' . strtoupper(bin2hex(random_bytes(8)));
        } while (self::where('qr_degeri', $qr)->exists());
        
        return $qr;
    }
}