<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model {
    protected $guarded = [];
    protected $casts = ['checked_in_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function trip() { return $this->belongsTo(Trip::class); }
    public function pickupStop() { return $this->belongsTo(RouteStop::class, 'pickup_stop_id'); }
    public function dropoffStop() { return $this->belongsTo(RouteStop::class, 'dropoff_stop_id'); }
    public function bookingSeats() { return $this->hasMany(BookingSeat::class); }
    public function review() { return $this->hasOne(Review::class); }
}