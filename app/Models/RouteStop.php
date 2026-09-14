<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model {
    protected $guarded = [];
    public function route() { return $this->belongsTo(Route::class); }
}