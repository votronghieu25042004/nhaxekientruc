<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    use Notifiable;
    protected $fillable = [
        'name', 'email', 'phone', 'role', 'password', 'status', 
        'reward_points', 'loyalty_tier', 'driver_id',
        'is_student', 'student_registered_at', 'student_discount_expires_at', 
        'student_first_trip_used', 'membership_plan', 'membership_discount_percent', 'membership_expires_at'
    ];
    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_student' => 'boolean',
        'student_first_trip_used' => 'boolean',
        'student_registered_at' => 'datetime',
        'student_discount_expires_at' => 'datetime',
        'membership_expires_at' => 'datetime',
        'membership_discount_percent' => 'integer',
        'reward_points' => 'integer',
    ];

    public function bookings() { return $this->hasMany(Booking::class); }
    public function reviews() { return $this->hasMany(Review::class); }

    /**
     * Kiểm tra người dùng có phải sinh viên hay không (email .edu.vn hoặc có cờ is_student)
     */
    public function getIsStudentAttribute($value): bool
    {
        if ($value) return true;
        $email = strtolower($this->email ?? '');
        return str_contains($email, '.edu.vn') || str_contains($email, 'sinhvien') || ($this->role === 'student');
    }

    /**
     * Tính số ngày còn lại của gói ưu đãi sinh viên / hội viên
     */
    public function getStudentDaysRemainingAttribute(): int
    {
        $plan = strtoupper((string)($this->membership_plan ?? ''));
        if ($plan === 'CANCELLED' || $plan === 'STANDARD' || $plan === 'THUONG' || empty($plan)) {
            return 0;
        }
        $expireDate = $this->membership_expires_at ?? $this->student_discount_expires_at;
        if (!$expireDate) return 0;
        $diff = now()->diffInDays($expireDate, false);
        return max(0, (int) ceil($diff));
    }

    /**
     * Tổng số ngày của gói (365 ngày)
     */
    public function getStudentDaysTotalAttribute(): int
    {
        return 365;
    }

    /**
     * Trạng thái gói hội viên / sinh viên còn hiệu lực
     */
    public function getIsMembershipActiveAttribute(): bool
    {
        $plan = strtoupper((string)($this->membership_plan ?? ''));
        if ($plan === 'CANCELLED' || $plan === 'STANDARD' || $plan === 'THUONG' || empty($plan)) {
            return false;
        }
        $expireDate = $this->membership_expires_at ?? $this->student_discount_expires_at;
        if (!$expireDate) return false;
        return now()->lt($expireDate);
    }

    /**
     * Nhãn gói hội viên
     */
    public function getMembershipBadgeAttribute(): string
    {
        $plan = strtoupper((string)($this->membership_plan ?? ''));
        if ($plan === 'CANCELLED' || $plan === 'STANDARD' || $plan === 'THUONG' || empty($plan)) {
            return 'Hành Khách Thường';
        }
        if (str_contains($plan, 'ULTRA')) {
            return 'Hội Viên Ultra VIP (-40%)';
        }
        if (str_contains($plan, 'PRO')) {
            return $this->is_student ? 'Sinh Viên Pro (-30%)' : 'Hội Viên Pro (-30%)';
        }
        if (str_contains($plan, 'ECO')) {
            return 'Hội Viên Eco (-20%)';
        }
        return 'Hành Khách Thường';
    }
}