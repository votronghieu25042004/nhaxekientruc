<?php
use App\Models\Route;
use App\Models\Trip;
use App\Models\Bus;
use App\Models\RouteStop;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ROUTES ===\n";
foreach (Route::with('stops')->get() as $r) {
    echo "ID: {$r->id} | Name: {$r->name} | Origin: '{$r->origin}' | Dest: '{$r->destination}' | Departure Loc: '{$r->departure_location}' | Arrival Loc: '{$r->arrival_location}'\n";
    foreach ($r->stops as $s) {
        echo "  - Stop #{$s->stop_order}: {$s->stop_name} ({$s->stop_type})\n";
    }
}

echo "\n=== TRIPS ===\n";
foreach (Trip::with(['route', 'bus', 'driver'])->get() as $t) {
    echo "Trip ID: {$t->id} | Code: {$t->trip_code} | Route: {$t->route?->name} | Dep Time: {$t->departure_time} | Status: {$t->status} | Floor1: {$t->floor1_price} | Floor2: {$t->floor2_price}\n";
}
