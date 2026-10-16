<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model {
    protected $guarded = [];
    protected $casts = ['license_expiry' => 'date'];

    public function trips() { return $this->hasMany(Trip::class); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function updateAverageRating() {
        $avg = $this->reviews()->avg('rating');
        $this->avg_rating = $avg ? round($avg, 1) : 5.0;
        $this->total_trips = $this->trips()->where('status', 'COMPLETED')->count();
        $this->save();
    }
}