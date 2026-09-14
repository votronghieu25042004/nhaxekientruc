<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model {
    protected $guarded = [];
    protected $casts = ['amenities' => 'array'];
    public function trips() { return $this->hasMany(Trip::class); }
}