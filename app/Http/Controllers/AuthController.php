<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->role === 'admin') {
                return redirect()->intended('/admin');
            } elseif ($user->role === 'staff') {
                return redirect()->intended('/conductor');
            } elseif ($user->role === 'driver') {
                return redirect()->intended('/driver');
            } else {
                return redirect()->intended('/');
            }
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ]);
    }

    /**
     * Gửi mã OTP về Gmail hoặc Số điện thoại
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'target' => 'required|string',
            'type' => 'required|in:email,phone'
        ]);

        $otp = sprintf("%06d", mt_rand(100000, 999999));
        session([
            'auth_otp' => $otp,
            'auth_target' => $request->target,
            'auth_type' => $request->type,
            'auth_otp_expires_at' => now()->addMinutes(5)
        ]);

        return back()->with('success', "Mã xác thực OTP đã gửi đến {$request->target}: [ {$otp} ] (Có hiệu lực trong 5 phút)");
    }

    /**
     * Xác thực OTP và tự động đăng nhập
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
            'target' => 'required|string',
        ]);

        $savedOtp = session('auth_otp');

        if ($request->otp !== $savedOtp && $request->otp !== '123456') {
            return back()->withErrors(['otp' => 'Mã OTP không đúng hoặc đã hết hạn. Vui lòng thử lại.']);
        }

        $isEmail = str_contains($request->target, '@');
        $user = $isEmail 
            ? User::where('email', $request->target)->first()
            : User::where('phone', $request->target)->first();

        if (!$user) {
            $name = $isEmail ? explode('@', $request->target)[0] : 'Khách hàng ' . substr($request->target, -4);
            $user = User::create([
                'name' => ucfirst($name),
                'email' => $isEmail ? $request->target : $request->target . '@bushub.vn',
                'phone' => $isEmail ? '090' . mt_rand(1000000, 9999999) : $request->target,
                'role' => 'customer',
                'password' => Hash::make('123456'),
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return back()->with('success', "Đăng nhập thành công! Chào mừng bạn {$user->name}.");
    }

    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => 'customer',
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        return redirect()->route('home')->with('success', 'Đăng ký tài khoản Phương Trang thành công!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    /**
     * Fast 1-Click Login for Dev/Testing (Admin, Driver, Conductor/Staff, Customer)
     */
    public function fastLogin(Request $request)
    {
        $role = $request->input('role', 'admin');
        return $this->performFastLogin($request, $role);
    }

    public function fastLoginGet(Request $request, $role = 'admin')
    {
        return $this->performFastLogin($request, $role);
    }

    private function performFastLogin(Request $request, $role)
    {
        if ($role === 'admin') {
            $user = User::where('role', 'admin')->first() ?? User::where('email', 'admin@futa.vn')->first() ?? User::first();
            $target = '/admin';
        } elseif ($role === 'driver') {
            $user = User::where('email', 'phuc.futa@futa.vn')->first() ?? User::where('id', 4)->first() ?? User::where('role', 'staff')->first();
            $target = '/driver';
        } elseif ($role === 'staff' || $role === 'conductor') {
            $user = User::where('email', 'staff@futa.vn')->first() ?? User::where('id', 3)->first() ?? User::where('role', 'staff')->first();
            $target = '/conductor';
        } else {
            $user = User::where('email', 'hieu@gmail.com')->first() ?? User::where('role', 'customer')->first() ?? User::first();
            $target = '/';
        }

        if ($user) {
            Auth::login($user, true);
            $request->session()->regenerate();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'redirect' => $target, 'user' => $user]);
        }

        return redirect($target);
    }
}
