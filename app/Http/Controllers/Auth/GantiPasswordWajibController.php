<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class GantiPasswordWajibController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Auth/GantiPasswordWajib');
    }

    public function update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
            'harus_ganti_password' => false,
        ]);

        return redirect()->route('dashboard');
    }
}
