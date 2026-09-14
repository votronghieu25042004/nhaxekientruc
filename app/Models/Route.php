<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Route extends Model {
    protected $guarded = [];
    public function stops() { return $this->hasMany(RouteStop::class)->orderBy('stop_order', 'asc'); }
    public function fares() { return $this->hasMany(RouteFare::class); }
    public function trips() { return $this->hasMany(Trip::class); }
}