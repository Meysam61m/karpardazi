<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:200'],
        ], [
            'required' => 'وارد کردن :attribute الزامی است.',
        ], [
            'username' => 'نام کاربری',
            'password' => 'رمز عبور',
        ]);

        $ok = Auth::attempt([
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'is_active' => 1,
        ], $request->boolean('remember'));

        if (! $ok) {
            AuditLog::record('login_failed', null, null, ['username' => $credentials['username']]);

            throw ValidationException::withMessages([
                'username' => 'نام کاربری یا رمز عبور اشتباه است، یا حساب غیرفعال شده است.',
            ]);
        }

        $request->session()->regenerate();
        AuditLog::record('login');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        AuditLog::record('logout');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
