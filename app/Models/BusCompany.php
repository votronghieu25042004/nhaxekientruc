<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BusCompany extends Model {
    protected $guarded = [];
    public function buses() { return $this->hasMany(Bus::class); }
    public function trips() { return $this->hasMany(Trip::class); }
}