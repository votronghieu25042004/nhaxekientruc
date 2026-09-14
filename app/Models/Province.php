<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Province extends Model {
    protected $guarded = [];
    public function stations() { return $this->hasMany(BusStation::class); }
}