<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model {
    protected $guarded = [];
    protected $casts = ['checked_in_at' => 'datetime'];
    protected $appends = ['passenger_name', 'passenger_phone', 'ticket_code', 'total_price', 'total_seats'];

    public function getPassengerNameAttribute() {
        return $this->customer_name ?? $this->attributes['customer_name'] ?? 'Hành khách';
    }
    public function getPassengerPhoneAttribute() {
        return $this->customer_phone ?? $this->attributes['customer_phone'] ?? '0905.123.456';
    }
    public function getTicketCodeAttribute() {
        return $this->booking_code ?? $this->attributes['booking_code'] ?? 'KT-000000';
    }
    public function getTotalPriceAttribute() {
        return $this->total_amount ?? $this->attributes['total_amount'] ?? 140000;
    }
    public function getTotalSeatsAttribute() {
        if (isset($this->attributes['total_seats']) && (int)$this->attributes['total_seats'] > 0) {
            return (int)$this->attributes['total_seats'];
        }
        if ($this->relationLoaded('bookingSeats') && $this->bookingSeats->isNotEmpty()) {
            return $this->bookingSeats->count();
        }
        if ($this->relationLoaded('seats') && $this->seats->isNotEmpty()) {
            return $this->seats->count();
        }
        return (int)($this->attributes['total_seats_count'] ?? 1);
    }

    public function user() { return $this->belongsTo(User::class); }
    public function trip() { return $this->belongsTo(Trip::class); }
    public function pickupStop() { return $this->belongsTo(RouteStop::class, 'pickup_stop_id'); }
    public function dropoffStop() { return $this->belongsTo(RouteStop::class, 'dropoff_stop_id'); }
    public function seats() { return $this->hasMany(BookingSeat::class); }
    public function bookingSeats() { return $this->hasMany(BookingSeat::class); }
    public function review() { return $this->hasOne(Review::class); }
}