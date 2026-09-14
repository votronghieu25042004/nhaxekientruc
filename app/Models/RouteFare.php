<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RouteFare extends Model {
    protected $guarded = [];
    public function route() { return $this->belongsTo(Route::class); }
    public function pickupStop() { return $this->belongsTo(RouteStop::class, 'pickup_stop_id'); }
    public function dropoffStop() { return $this->belongsTo(RouteStop::class, 'dropoff_stop_id'); }
}