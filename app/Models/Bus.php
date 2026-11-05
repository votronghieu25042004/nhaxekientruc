<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Bus extends Model {
    protected $guarded = [];
    protected $casts = ['amenities' => 'array'];
    protected $appends = ['plate_number', 'model'];

    public function getPlateNumberAttribute() {
        return $this->license_plate ?? $this->attributes['license_plate'] ?? '43B-012.34';
    }

    public function getModelAttribute() {
        return $this->bus_type ?? $this->attributes['bus_type'] ?? 'Giường Nằm VIP';
    }

    public function trips() { return $this->hasMany(Trip::class); }
}