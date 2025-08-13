<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    // Kullanıcı Girişi
    public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'sifre' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Manuel kullanıcı kontrolü (auth()->attempt() yerine)
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->sifre, $user->sifre)) {
        return response()->json(['error' => 'Email veya şifre hatalı'], 401);
    }

    // JWT token oluştur
    try {
    $token = JWTAuth::fromUser($user);
} catch (\Exception $e) {
    return response()->json([
        'error' => 'JWT oluşturulurken hata',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 500);
}

    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => auth('api')->factory()->getTTL() * 60,
        'user' => [
            'id' => (string) $user->_id,
            'ad_soyad' => $user->ad_soyad,
            'email' => $user->email,
            'rol' => $user->rol,
        ]
    ]);
}

    public function loginDebug(Request $request)
{
    $user = User::where('email', $request->email)->first();
    
    return response()->json([
        'user_found' => $user ? 'Evet' : 'Hayır',
        'email' => $request->email,
        'password_input' => $request->sifre,
        'user_data' => $user ? [
            'id' => $user->id,
            'email' => $user->email,
            'ad_soyad' => $user->ad_soyad,
            'aktif_mi' => $user->aktif_mi,
            'rol' => $user->rol
        ] : null,
        'password_check' => $user ? Hash::check($request->sifre, $user->sifre) : false
    ]);
}

    // Kullanıcı Kaydı
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ad_soyad' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:kullanicilar',
            'sifre' => 'required|string|min:6',
            'rol' => 'required|in:admin,user',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create([
            'ad_soyad' => $request->get('ad_soyad'),
            'email' => $request->get('email'),
            'sifre' => Hash::make($request->get('sifre')),
            'rol' => $request->get('rol'),
            'aktif_mi' => true,
            'olusturma_tarihi' => now(),
            'guncelleme_tarihi' => now(),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Kullanıcı başarıyla oluşturuldu',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    // Çıkış
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Başarıyla çıkış yapıldı']);
    }

    // Token Yenileme
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    // Kullanıcı Bilgileri
    public function userProfile()
    {
        return response()->json(auth()->user());
    }

    // Token Response Helper
    protected function respondWithToken($token)
    {
        $user = auth()->user();
        
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => [
                'id' => $user->id,
                'ad_soyad' => $user->ad_soyad,
                'email' => $user->email,
                'rol' => $user->rol,
            ]
        ]);
    }
}