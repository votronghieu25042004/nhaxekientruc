<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'reward_points' => (int)($user->reward_points ?? 0),
                    'loyalty_tier' => $user->loyalty_tier ?? 'MEMBER',
                    'is_student' => str_contains(strtolower($user->email ?? ''), '.edu.vn') || str_contains(strtolower($user->email ?? ''), 'sinhvien'),
                    'is_dau_student' => str_contains(strtolower($user->email ?? ''), 'dau.edu.vn'),
                    'student_badge' => str_contains(strtolower($user->email ?? ''), 'dau.edu.vn')
                        ? '🎓 Sinh Viên Kiến Trúc (DAU)'
                        : (str_contains(strtolower($user->email ?? ''), '.edu.vn') ? '🎓 Sinh Viên Đại Học' : null),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'appName' => config('app.name', 'nhaxekientruc'),
        ]);
    }
}
