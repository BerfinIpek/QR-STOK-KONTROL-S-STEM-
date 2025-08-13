<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cihaz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;
/*$cihazlar = \App\Models\Cihaz::all(); */


class CihazController extends Controller
{
    // Cihaz Listeleme (Filtreleme ile)
    public function index(Request $request)
    {
        $query = Cihaz::query();

        // Filtreleme
        if ($request->has('kategori') && $request->kategori) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->has('durum') && $request->durum) {
            $query->where('durum', $request->durum);
        }

        if ($request->has('arama') && $request->arama) {
            $arama = $request->arama;
            $query->where(function($q) use ($arama) {
                $q->where('cihaz_adi', 'like', "%{$arama}%")
                  ->orWhere('marka', 'like', "%{$arama}%")
                  ->orWhere('model', 'like', "%{$arama}%")
                  ->orWhere('seri_no', 'like', "%{$arama}%");
            });
        }

        // Kullanıcı kendi cihazlarını görsün (Admin hepsini görebilir)
        if (auth()->user()->rol !== 'admin') {
            $query->where('ekleyen_kullanici', auth()->user()->id);
        }

        $cihazlar = $query->orderBy('olusturma_tarihi', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $cihazlar
        ]);
    }

    // Tekil Cihaz Getirme
    public function show($id)
    {
        $cihaz = Cihaz::find($id);

        if (!$cihaz) {
            return response()->json(['error' => 'Cihaz bulunamadı'], 404);
        }

        // Kullanıcı sadece kendi cihazını görebilir (Admin hepsini görebilir)
        if (auth()->user()->rol !== 'admin' && $cihaz->ekleyen_kullanici !== auth()->user()->id) {
            return response()->json(['error' => 'Bu cihaza erişim yetkiniz yok'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $cihaz
        ]);
    }

    // Cihaz Ekleme
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cihaz_adi' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'kategori' => 'required|string|max:100',
            'marka' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'seri_no' => 'nullable|string|max:100',
            'qr_degeri' => 'nullable|string|max:255|unique:cihazlar,qr_degeri',
            'konum' => 'nullable|string|max:255',
            'durum' => 'required|in:aktif,pasif,bakim',
            'ekleyen_kullanici' => 'nullable|integer|exists:kullanici,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Benzersiz ID ve QR değeri oluştur
        $cihazId = Cihaz::generateUniqueId();
        $qrDegeri = Cihaz::generateQrValue();

        $cihaz = Cihaz::create([
            'cihaz_id' => $cihazId,
            'cihaz_adi' => $request->cihaz_adi,
            'aciklama' => $request->aciklama,
            'kategori' => $request->kategori,
            'marka' => $request->marka,
            'model' => $request->model,
            'seri_no' => $request->seri_no,
            'qr_degeri' => $qrDegeri,
            'durum' => $request->durum,
            'konum' => $request->konum,
            'ekleyen_kullanici' => auth()->check() ? auth()->user()->id : null,
            'olusturma_tarihi' => now(),
            'guncelleme_tarihi' => now(),
        ]);

        // QR kod oluştur ve kaydet
        $this->generateQrCode($cihaz);

        return response()->json([
            'success' => true,
            'message' => 'Cihaz başarıyla eklendi',
            'data' => $cihaz
        ], 201);
    }

    // Cihaz Güncelleme
    public function update(Request $request, $id)
    {
        $cihaz = Cihaz::find($id);

        if (!$cihaz) {
            return response()->json(['error' => 'Cihaz bulunamadı'], 404);
        }

        // Kullanıcı sadece kendi cihazını güncelleyebilir (Admin hepsini güncelleyebilir)
        if (auth()->user()->rol !== 'admin' && $cihaz->ekleyen_kullanici !== auth()->user()->id) {
            return response()->json(['error' => 'Bu cihazı güncelleme yetkiniz yok'], 403);
        }

        $validator = Validator::make($request->all(), [
            'cihaz_adi' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'kategori' => 'required|string|max:100',
            'marka' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'seri_no' => 'nullable|string|max:100',
            'konum' => 'nullable|string|max:255',
            'durum' => 'required|in:aktif,pasif,bakim'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cihaz->update([
            'cihaz_adi' => $request->cihaz_adi,
            'aciklama' => $request->aciklama,
            'kategori' => $request->kategori,
            'marka' => $request->marka,
            'model' => $request->model,
            'seri_no' => $request->seri_no,
            'konum' => $request->konum,
            'durum' => $request->durum,
            'guncelleme_tarihi' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cihaz başarıyla güncellendi',
            'data' => $cihaz
        ]);
    }

    // Cihaz Silme
    public function destroy($id)
    {
        $cihaz = Cihaz::find($id);

        if (!$cihaz) {
            return response()->json(['error' => 'Cihaz bulunamadı'], 404);
        }

        // Kullanıcı sadece kendi cihazını silebilir (Admin hepsini silebilir)
        if (auth()->user()->rol !== 'admin' && $cihaz->ekleyen_kullanici !== auth()->user()->id) {
            return response()->json(['error' => 'Bu cihazı silme yetkiniz yok'], 403);
        }

        // QR kod dosyasını sil
        $qrPath = "qr-codes/{$cihaz->cihaz_id}.png";
        if (Storage::disk('public')->exists($qrPath)) {
            Storage::disk('public')->delete($qrPath);
        }

        $cihaz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cihaz başarıyla silindi'
        ]);
    }

    // QR Kod Oluşturma
    private function generateQrCode($cihaz)
    {
        // QR kod verisini hazırla
        $qrData = [
            'cihaz_id' => $cihaz->cihaz_id,
            'qr_degeri' => $cihaz->qr_degeri,
            'cihaz_adi' => $cihaz->cihaz_adi,
            'url' => url("/cihaz/{$cihaz->cihaz_id}")
        ];

        // QR kod oluştur
        $qrImage = QrCode::format('png')
                         ->size(300)
                         ->margin(2)
                         ->generate(json_encode($qrData));

        // QR kodu kaydet
        $qrPath = "qr-codes/{$cihaz->cihaz_id}.png";
        Storage::disk('public')->put($qrPath, $qrImage);

        return $qrPath;
    }

    // QR Kod İndirme
    public function downloadQr($id)
    {
        $cihaz = Cihaz::find($id);

        if (!$cihaz) {
            return response()->json(['error' => 'Cihaz bulunamadı'], 404);
        }

        $qrPath = "qr-codes/{$cihaz->cihaz_id}.png";
        
        if (!Storage::disk('public')->exists($qrPath)) {
            // QR kod yoksa yeniden oluştur
            $this->generateQrCode($cihaz);
        }

        return Storage::disk('public')->download($qrPath, "QR-{$cihaz->cihaz_adi}.png");
    }

    // QR Kod Okutma
    public function scanQr(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_data' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // QR data'yı parse et
        $qrData = json_decode($request->qr_data, true);

        if (!$qrData || !isset($qrData['qr_degeri'])) {
            return response()->json(['error' => 'Geçersiz QR kod'], 400);
        }

        // Cihazı bul
        $cihaz = Cihaz::where('qr_degeri', $qrData['qr_degeri'])->first();

        if (!$cihaz) {
            return response()->json(['error' => 'Cihaz bulunamadı'], 404);
        }

        // Log kaydet (isteğe bağlı)
        $this->logQrScan($cihaz->id);

        return response()->json([
            'success' => true,
            'message' => 'QR kod başarıyla okundu',
            'data' => $cihaz
        ]);
    }

    // QR Okuma Logu (İsteğe bağlı)
    private function logQrScan($cihazId)
    {
        // QR okuma loglarını tutmak isterseniz buraya yazabilirsiniz
        // Şimdilik basit bir log tutalım
        \Log::info("QR Kod Okundu", [
            'cihaz_id' => $cihazId,
            'kullanici_id' => auth()->user()->id,
            'zaman' => now()
        ]);
    }
}