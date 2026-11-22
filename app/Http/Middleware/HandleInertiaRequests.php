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
                    'name' => trim(preg_replace('/^Sinh\s*vi[êe]n\s+/ui', '', (string)$user->name)),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'reward_points' => (int)($user->reward_points ?? 0),
                    'loyalty_tier' => $user->loyalty_tier ?? 'MEMBER',
                    'is_student' => $user->is_student,
                    'student_badge' => $user->is_student ? '🎓 Sinh viên' : null,
                    'student_days_remaining' => $user->student_days_remaining,
                    'student_days_total' => $user->student_days_total,
                    'student_registered_at' => $user->student_registered_at ? $user->student_registered_at->format('d/m/Y') : ($user->created_at ? $user->created_at->format('d/m/Y') : null),
                    'student_discount_expires_at' => $user->student_discount_expires_at ? $user->student_discount_expires_at->format('d/m/Y') : null,
                    'student_first_trip_used' => (bool)$user->student_first_trip_used,
                    'membership_plan' => $user->membership_plan ?? ($user->is_student ? 'PRO' : 'STANDARD'),
                    'membership_discount_percent' => (int)($user->membership_discount_percent ?? ($user->is_student ? 30 : 0)),
                    'membership_registered_at' => $user->student_registered_at ? $user->student_registered_at->format('d/m/Y') : ($user->created_at ? $user->created_at->format('d/m/Y') : now()->format('d/m/Y')),
                    'membership_expires_at' => ($user->membership_expires_at ?? $user->student_discount_expires_at) ? ($user->membership_expires_at ?? $user->student_discount_expires_at)->format('d/m/Y') : null,
                    'membership_badge' => $user->membership_badge,
                    'is_membership_active' => (bool)$user->is_membership_active,
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
