<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    use Notifiable;
    protected $fillable = ['name', 'email', 'phone', 'role', 'password', 'status', 'reward_points', 'loyalty_tier', 'driver_id'];
    protected $hidden = ['password', 'remember_token'];

    public function bookings() { return $this->hasMany(Booking::class); }
    public function reviews() { return $this->hasMany(Review::class); }
}