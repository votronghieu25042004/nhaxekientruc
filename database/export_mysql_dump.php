<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = ['users', 'drivers', 'buses', 'routes', 'trips', 'seats', 'bookings', 'booking_seats', 'reviews'];
$sql = "-- ========================================================\n";
$sql .= "-- CO SO DU LIEU: FUTA BUS BOOKING (NHA XE PHUONG TRANG)\n";
$sql .= "-- Nguon: Laravel SmartBus Project (Vo Trong Hieu - 22CT3)\n";
$sql .= "-- Import truc tiep vao XAMPP phpMyAdmin\n";
$sql .= "-- ========================================================\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $rows = DB::table($table)->get();
        $sql .= "-- Dumping data for table `$table`\n";
        foreach ($rows as $row) {
            $array = (array) $row;
            $keys = array_keys($array);
            $values = array_map(function($v) {
                if (is_null($v)) return 'NULL';
                return "'" . addslashes((string)$v) . "'";
            }, array_values($array));

            $sql .= "INSERT INTO `$table` (`" . implode('`, `', $keys) . "`) VALUES (" . implode(', ', $values) . ");\n";
        }
        $sql .= "\n";
    }
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
file_put_contents(__DIR__ . '/futa_bus_booking.sql', $sql);
echo "Exported futa_bus_booking.sql successfully!\n";
