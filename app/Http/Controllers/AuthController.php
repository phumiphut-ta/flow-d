<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login request (username + password only, no email).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'กรุณากรอกชื่อผู้ใช้งาน',
            'password.required' => 'กรุณากรอกรหัสผ่าน',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password'], 'is_active' => true], $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'username' => 'ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง หรือบัญชีถูกระงับการใช้งาน',
        ]);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show change password form.
     */
    public function showProfile(): View
    {
        return view('auth.profile', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'กรุณากรอกรหัสผ่านปัจจุบัน',
            'current_password.current_password' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง',
            'password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'password.min' => 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร',
            'password.confirmed' => 'การยืนยันรหัสผ่านใหม่ไม่ตรงกัน',
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
    }
}
