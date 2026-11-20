<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAKS_PERCOBAAN = 5;

    public function create(): View
    {
        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required'],
        ]);

        // Batas percobaan dihitung per akun + alamat IP: 5 kali gagal, lalu terkunci 1 menit.
        $kunci = Str::lower($data['login']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN)) {
            throw ValidationException::withMessages([
                'login' => 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik.',
            ]);
        }

        // Boleh login dengan email atau username.
        $kolom = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$kolom => $data['login'], 'password' => $data['password']], $request->boolean('ingat_saya'))) {
            RateLimiter::hit($kunci, 60);

            // Pesan sengaja umum agar tidak membocorkan apakah akun terdaftar.
            return back()->withErrors(['login' => 'Email/username atau password salah.'])->onlyInput('login');
        }

        RateLimiter::clear($kunci);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.pesanan.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
