<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model {
    protected $guarded = [];
    public function trips() { return $this->hasMany(Trip::class); }
    public function reviews() { return $this->hasMany(Review::class); }

    public function updateAverageRating() {
        $avg = $this->reviews()->avg('rating');
        $this->avg_rating = $avg ? round($avg, 1) : 5.0;
        $this->save();
    }
}