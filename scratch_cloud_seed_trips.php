<?php

$host = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
$port = 4000;
$user = '25JeqZG3kRmnPq8.root';
$pass = '1Fbk9ONUPLRYmfPJ';
$db   = 'nhaxe_kientruc';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_SSL_CA => true,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
];

$pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, $options);

echo "Checking trips count in TiDB Cloud...\n";
$tripsCount = $pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn();
echo "Current trips in DB: " . $tripsCount . "\n";

// Kiểm tra chuyến của hôm nay và các ngày tới
$today = date('Y-m-d');
$todayCount = $pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(departure_time) = '$today'")->fetchColumn();
echo "Trips for today ($today): " . $todayCount . "\n";

$buses = $pdo->query("SELECT id FROM buses")->fetchAll(PDO::FETCH_COLUMN);
$drivers = $pdo->query("SELECT id FROM drivers")->fetchAll(PDO::FETCH_COLUMN);
$conductors = $pdo->query("SELECT id FROM users WHERE role = 'staff'")->fetchAll(PDO::FETCH_COLUMN);
$routes = $pdo->query("SELECT id, origin, destination, distance_km, estimated_hours FROM routes")->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($buses) . " buses, " . count($drivers) . " drivers, " . count($conductors) . " conductors, " . count($routes) . " routes.\n";

if (empty($conductors)) {
    // Lấy tài xế hoặc admin làm conductor_id nếu chưa có staff
    $conductors = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
}

// Sinh đầy đủ chuyến cho 7 ngày: từ 2 ngày trước đến 4 ngày tới
$dates = [];
for ($i = -2; $i <= 4; $i++) {
    $dates[] = date('Y-m-d', strtotime("$i days"));
}

$stmtTrip = $pdo->prepare("INSERT INTO trips 
    (trip_code, route_id, bus_id, driver_id, conductor_id, departure_time, arrival_time, base_price, floor1_price, floor2_price, status, current_lat, current_lng, current_speed_kmh, gps_updated_at, departed_at, completed_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$insertedTrips = 0;

foreach ($dates as $d) {
    $cnt = $pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(departure_time) = '$d'")->fetchColumn();
    if ($cnt >= 12) {
        echo "Date $d already has $cnt trips, skipping.\n";
        continue;
    }

    echo "Generating trips for $d...\n";
    $timeSlots = [
        ['06:00', 2.5, 140000],
        ['07:30', 5.5, 195000],
        ['08:30', 2.5, 140000],
        ['09:45', 6.0, 240000],
        ['11:00', 2.5, 140000],
        ['13:30', 2.5, 140000],
        ['14:45', 5.5, 200000],
        ['16:15', 2.5, 150000],
        ['17:30', 2.5, 150000],
        ['19:00', 6.0, 240000],
        ['20:30', 10.0, 320000],
        ['22:00', 5.5, 210000],
    ];

    $nowTime = time();

    foreach ($timeSlots as $idx => $slot) {
        $depTimeStr = "$d {$slot[0]}:00";
        $depTs = strtotime($depTimeStr);
        $arrTs = $depTs + (int)($slot[1] * 3600);
        $arrTimeStr = date('Y-m-d H:i:s', $arrTs);

        if ($nowTime >= $arrTs) {
            $status = 'COMPLETED';
        } elseif ($nowTime >= $depTs && $nowTime < $arrTs) {
            $status = 'IN_TRANSIT';
        } elseif (($depTs - $nowTime) <= 2700 && $nowTime < $depTs) {
            $status = 'BOARDING';
        } else {
            $status = 'OPEN_FOR_SALE';
        }

        $route = $routes[$idx % count($routes)];
        $busId = $buses[$idx % count($buses)];
        $driverId = $drivers[$idx % count($drivers)];
        $conductorId = $conductors[$idx % count($conductors)];
        $tripCode = 'KT-' . date('md', $depTs) . '-' . str_pad($idx + 1, 3, '0', STR_PAD_LEFT);

        // Check exists
        $chk = $pdo->query("SELECT id FROM trips WHERE bus_id = $busId AND departure_time = '$depTimeStr'")->fetchColumn();
        if ($chk) continue;

        $stmtTrip->execute([
            $tripCode,
            $route['id'],
            $busId,
            $driverId,
            $conductorId,
            $depTimeStr,
            $arrTimeStr,
            $slot[2],
            $slot[2],
            max(50000, $slot[2] - 10000),
            $status,
            16.0544 + (rand(-50, 50) / 10000),
            108.2022 + (rand(-50, 50) / 10000),
            $status === 'IN_TRANSIT' ? rand(60, 78) : 0,
            $status === 'IN_TRANSIT' ? date('Y-m-d H:i:s') : null,
            in_array($status, ['IN_TRANSIT', 'COMPLETED']) ? $depTimeStr : null,
            $status === 'COMPLETED' ? $arrTimeStr : null
        ]);
        $insertedTrips++;
    }
}

echo "Inserted $insertedTrips new trips across past and future days!\n";

// Thêm khách đặt vé mẫu cho các chuyến hôm nay và quá khứ để có dữ liệu tài xế/lơ xe
$tripsWithoutBookings = $pdo->query("SELECT t.id, t.status, t.departure_time, t.floor1_price, t.floor2_price, t.base_price, t.route_id 
    FROM trips t 
    LEFT JOIN bookings b ON t.id = b.trip_id 
    WHERE b.id IS NULL 
    LIMIT 40")->fetchAll(PDO::FETCH_ASSOC);

echo "Populating bookings for " . count($tripsWithoutBookings) . " trips...\n";

$users = $pdo->query("SELECT id, name, phone, email FROM users WHERE role = 'customer' LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

$stmtBooking = $pdo->prepare("INSERT INTO bookings 
    (booking_code, trip_id, user_id, booking_source, customer_name, customer_phone, customer_email, pickup_stop_id, dropoff_stop_id, pickup_order, dropoff_order, total_seats, total_amount, paid_amount, payment_status, payment_method, checkin_status, checkin_method, checked_in_at, is_boarded, boarding_status, qr_token, notes, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtSeat = $pdo->prepare("INSERT INTO booking_seats (booking_id, trip_id, seat_number, floor, pickup_order, dropoff_order, price) VALUES (?, ?, ?, ?, ?, ?, ?)");

$bookingsCount = 0;
foreach ($tripsWithoutBookings as $trip) {
    // Lấy điểm đón và trả của tuyến
    $stops = $pdo->query("SELECT id, stop_order FROM route_stops WHERE route_id = " . (int)$trip['route_id'] . " ORDER BY stop_order ASC")->fetchAll(PDO::FETCH_ASSOC);
    $firstStop = $stops[0] ?? ['id' => 1, 'stop_order' => 1];
    $lastStop = end($stops) ?: ['id' => 2, 'stop_order' => 2];

    $isPast = strtotime($trip['departure_time']) < time();
    $numCust = rand(6, 12);

    for ($k = 0; $k < $numCust; $k++) {
        $u = $users[$k % count($users)];
        $seatNum = ($k % 2 === 0 ? 'A' : 'B') . str_pad($k + 1, 2, '0', STR_PAD_LEFT);
        $floor = $k % 2 === 0 ? 1 : 2;
        $price = $floor === 1 ? (float)$trip['floor1_price'] : (float)$trip['floor2_price'];
        $checkin = $isPast ? 'CHECKED_IN' : 'WAITING';
        $bCode = 'KT-' . strtoupper(bin2hex(random_bytes(4)));
        $qrToken = 'QR-' . strtoupper(bin2hex(random_bytes(6)));

        $stmtBooking->execute([
            $bCode,
            $trip['id'],
            $u['id'],
            'ONLINE_WEB',
            $u['name'],
            $u['phone'],
            $u['email'],
            $firstStop['id'],
            $lastStop['id'],
            $firstStop['stop_order'],
            $lastStop['stop_order'],
            1,
            $price,
            $price,
            'PAID',
            'VNPAY',
            $checkin,
            $checkin === 'CHECKED_IN' ? 'QR_SCAN' : null,
            $checkin === 'CHECKED_IN' ? $trip['departure_time'] : null,
            $checkin === 'CHECKED_IN' ? 1 : 0,
            $checkin === 'CHECKED_IN' ? 'BOARDED' : 'NOT_BOARDED',
            $qrToken,
            'Đặt vé trực tuyến nhaxekientruc',
            $trip['departure_time'],
            $trip['departure_time']
        ]);
        $newBookingId = $pdo->lastInsertId();

        $stmtSeat->execute([
            $newBookingId,
            $trip['id'],
            $seatNum,
            $floor,
            $firstStop['stop_order'],
            $lastStop['stop_order'],
            $price
        ]);
        $bookingsCount++;
    }
}

echo "Created $bookingsCount bookings for active trips!\n";
echo "ALL TRIPS & PASSENGERS POPULATED ON CLOUD!\n";
