<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LostFound extends Model {
    protected $guarded = [];
    public function trip() { return $this->belongsTo(Trip::class); }
}