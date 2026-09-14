<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model {
    protected $guarded = [];
    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'departed_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'stop_status_logs' => 'array',
        'amenities' => 'array',
    ];

    public function route() { return $this->belongsTo(Route::class); }
    public function bus() { return $this->belongsTo(Bus::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
    public function conductor() { return $this->belongsTo(User::class, 'conductor_id'); }
    public function bookings() { return $this->hasMany(Booking::class); }
    public function bookingSeats() { return $this->hasMany(BookingSeat::class); }
    public function expenses() { return $this->hasMany(TripExpense::class); }
    public function closing() { return $this->hasOne(TripClosing::class); }
    public function incidents() { return $this->hasMany(Incident::class); }
    public function lostFounds() { return $this->hasMany(LostFound::class); }
}
