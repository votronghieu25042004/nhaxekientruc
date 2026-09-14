<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Seat extends Model {
    protected $guarded = [];
    public function trip() { return $this->belongsTo(Trip::class); }
}