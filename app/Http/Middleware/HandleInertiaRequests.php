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
                    'is_student' => $user->is_student,
                    'is_dau_student' => str_contains(strtolower($user->email ?? ''), 'dau.edu.vn'),
                    'student_badge' => str_contains(strtolower($user->email ?? ''), 'dau.edu.vn')
                        ? '🎓 Sinh Viên Kiến Trúc (DAU)'
                        : ($user->is_student ? '🎓 Sinh Viên Đại Học' : null),
                    'student_days_remaining' => $user->student_days_remaining,
                    'student_days_total' => $user->student_days_total,
                    'student_registered_at' => $user->student_registered_at ? $user->student_registered_at->format('d/m/Y') : ($user->created_at ? $user->created_at->format('d/m/Y') : null),
                    'student_discount_expires_at' => $user->student_discount_expires_at ? $user->student_discount_expires_at->format('d/m/Y') : null,
                    'student_first_trip_used' => (bool)$user->student_first_trip_used,
                    'membership_plan' => $user->membership_plan ?? 'STANDARD',
                    'membership_discount_percent' => $user->membership_discount_percent ?? ($user->is_student ? 30 : 0),
                    'membership_expires_at' => $user->membership_expires_at ? $user->membership_expires_at->format('d/m/Y') : null,
                    'membership_badge' => $user->membership_badge,
                    'is_membership_active' => $user->is_membership_active,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'otp_notice' => fn () => $request->session()->get('otp_notice'),
            ],
            'appName' => config('app.name', 'nhaxekientruc'),
        ]);
    }
}
