<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('perusahaan')->redirect();
    }

    public function callback()
    {
        $ssoUser = Socialite::driver('perusahaan')->user();
        
        dd($ssoUser->getRaw()); // SEMENTARA - untuk lihat field asli, hapus setelah tahu strukturnya
        
        // ...kode selanjutnya menyusul setelah kita tahu field yang benar
    }
}