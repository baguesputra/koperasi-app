<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Security\PinSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmablePasswordController extends Controller
{
    public function __construct(
        private PinSecurityService $pinService
    ) {}

    /**
     * Show the confirm PIN view for sensitive areas.
     */
    public function show(): Response
    {
        return Inertia::render('Auth/ConfirmPassword');
    }

    /**
     * Confirm the user's PIN.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'pin.required' => 'PIN keamanan wajib diisi.',
            'pin.size' => 'PIN keamanan harus tepat 6 digit angka.',
            'pin.regex' => 'PIN keamanan hanya boleh berisi angka.',
        ]);

        $user = $request->user();

        if (! $user->hasPin()) {
            throw ValidationException::withMessages([
                'pin' => 'Anda belum mengatur PIN keamanan. Silakan atur PIN terlebih dahulu.',
            ]);
        }

        $pinCheck = $this->pinService->verify($user, (string) $request->pin, $request);

        if (! $pinCheck['valid']) {
            if ($pinCheck['locked_out']) {
                return redirect()->route('login')->withErrors([
                    'pin' => $pinCheck['message'],
                ]);
            }

            throw ValidationException::withMessages([
                'pin' => $pinCheck['message'],
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
