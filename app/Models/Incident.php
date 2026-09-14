<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model {
    protected $guarded = [];
    public function trip() { return $this->belongsTo(Trip::class); }
    public function bus() { return $this->belongsTo(Bus::class); }
}