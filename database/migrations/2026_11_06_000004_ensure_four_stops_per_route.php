<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $routesData = [
            1 => [
                'stops' => [
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Trung chuyển Nội thành Đà Nẵng', 'address' => 'Ngã tư Nguyễn Sinh Sắc - Hoàng Thị Loan, Liên Chiểu, TP. Đà Nẵng', 'order' => 2, 'dist' => 8, 'mins' => 20],
                    ['name' => 'Trạm trả khách Lăng Cô / Cửa ngõ Nam Huế', 'address' => 'Thị trấn Lăng Cô, Huyện Phú Lộc, Thừa Thiên Huế', 'order' => 3, 'dist' => 45, 'mins' => 60],
                    ['name' => 'Bến xe Phía Nam Huế', 'address' => '57 An Dương Vương, Phường An Đông, TP. Huế', 'order' => 4, 'dist' => 100, 'mins' => 120],
                ]
            ],
            2 => [
                'stops' => [
                    ['name' => 'Bến xe Phía Nam Huế', 'address' => '57 An Dương Vương, Phường An Đông, TP. Huế', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Văn phòng Trung tâm TP. Huế', 'address' => '57 Hùng Vương, Phường Phú Hội, TP. Huế', 'order' => 2, 'dist' => 5, 'mins' => 15],
                    ['name' => 'Trạm trả khách Cửa ngõ Ngã ba Huế (Đà Nẵng)', 'address' => 'Cầu vượt Ngã ba Huế, Thanh Khê, TP. Đà Nẵng', 'order' => 3, 'dist' => 92, 'mins' => 105],
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 4, 'dist' => 100, 'mins' => 120],
                ]
            ],
            3 => [
                'stops' => [
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Trung chuyển Nội thành Đà Nẵng', 'address' => 'Ngã tư Nguyễn Sinh Sắc - Hoàng Thị Loan, Liên Chiểu, TP. Đà Nẵng', 'order' => 2, 'dist' => 8, 'mins' => 20],
                    ['name' => 'Trạm trả khách Dọc tuyến QL1A Hải Lăng - Quảng Trị', 'address' => 'Thị trấn Hải Lăng, Huyện Hải Lăng, Quảng Trị', 'order' => 3, 'dist' => 145, 'mins' => 180],
                    ['name' => 'Bến xe Đông Hà (Quảng Trị)', 'address' => '425 Lê Duẩn, Phường 2, TP. Đông Hà, Quảng Trị', 'order' => 4, 'dist' => 165, 'mins' => 210],
                ]
            ],
            4 => [
                'stops' => [
                    ['name' => 'Bến xe Đông Hà (Quảng Trị)', 'address' => '425 Lê Duẩn, Phường 2, TP. Đông Hà, Quảng Trị', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Trung chuyển Ngã tư Sậy Đông Hà', 'address' => 'QL1A, Phường 2, TP. Đông Hà, Quảng Trị', 'order' => 2, 'dist' => 6, 'mins' => 15],
                    ['name' => 'Trạm trả khách Cửa ngõ Ngã ba Huế (Đà Nẵng)', 'address' => 'Cầu vượt Ngã ba Huế, Thanh Khê, TP. Đà Nẵng', 'order' => 3, 'dist' => 157, 'mins' => 195],
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 4, 'dist' => 165, 'mins' => 210],
                ]
            ],
            5 => [
                'stops' => [
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Trung chuyển Nội thành Đà Nẵng', 'address' => 'Ngã tư Nguyễn Sinh Sắc - Hoàng Thị Loan, Liên Chiểu, TP. Đà Nẵng', 'order' => 2, 'dist' => 8, 'mins' => 20],
                    ['name' => 'Trạm trả khách Dọc tuyến Quán Hàu - Lệ Thủy', 'address' => 'QL1A, Thị trấn Quán Hàu, Quảng Bình', 'order' => 3, 'dist' => 245, 'mins' => 300],
                    ['name' => 'Bến xe Đồng Hới (Quảng Bình)', 'address' => 'Trần Hưng Đạo, TP. Đồng Hới, Quảng Bình', 'order' => 4, 'dist' => 270, 'mins' => 330],
                ]
            ],
            6 => [
                'stops' => [
                    ['name' => 'Bến xe Đồng Hới (Quảng Bình)', 'address' => 'Trần Hưng Đạo, TP. Đồng Hới, Quảng Bình', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Trung chuyển Nam Đồng Hới', 'address' => 'QL1A, Phường Phú Hải, TP. Đồng Hới, Quảng Bình', 'order' => 2, 'dist' => 7, 'mins' => 15],
                    ['name' => 'Trạm trả khách Cửa ngõ Ngã ba Huế (Đà Nẵng)', 'address' => 'Cầu vượt Ngã ba Huế, Thanh Khê, TP. Đà Nẵng', 'order' => 3, 'dist' => 262, 'mins' => 310],
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 4, 'dist' => 270, 'mins' => 330],
                ]
            ],
            7 => [
                'stops' => [
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Trung chuyển Nam Đà Nẵng (Hòa Cầm)', 'address' => 'Cửa ngõ Cầu Đỏ, Hòa Cầm, Cẩm Lệ, TP. Đà Nẵng', 'order' => 2, 'dist' => 12, 'mins' => 25],
                    ['name' => 'Trạm trả khách Cửa ngõ An Nhơn - Phú Tài', 'address' => 'QL1A, Ngã ba Phú Tài, TP. Quy Nhơn, Bình Định', 'order' => 3, 'dist' => 300, 'mins' => 340],
                    ['name' => 'Bến xe Quy Nhơn (Bình Định)', 'address' => '71 Tây Sơn, Ghềnh Ráng, TP. Quy Nhơn, Bình Định', 'order' => 4, 'dist' => 315, 'mins' => 360],
                ]
            ],
            8 => [
                'stops' => [
                    ['name' => 'Bến xe Quy Nhơn (Bình Định)', 'address' => '71 Tây Sơn, Ghềnh Ráng, TP. Quy Nhơn, Bình Định', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Cửa ngõ Phú Tài - Quy Nhơn', 'address' => 'Ngã ba Phú Tài, QL1A, TP. Quy Nhơn, Bình Định', 'order' => 2, 'dist' => 15, 'mins' => 20],
                    ['name' => 'Trạm trả khách Cửa ngõ Hòa Cầm (Đà Nẵng)', 'address' => 'Nút giao Hòa Cầm, QL1A, Cẩm Lệ, TP. Đà Nẵng', 'order' => 3, 'dist' => 303, 'mins' => 340],
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 4, 'dist' => 315, 'mins' => 360],
                ]
            ],
            9 => [
                'stops' => [
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Trung chuyển Nam Đà Nẵng (Hòa Cầm)', 'address' => 'Cửa ngõ Cầu Đỏ, Hòa Cầm, Cẩm Lệ, TP. Đà Nẵng', 'order' => 2, 'dist' => 12, 'mins' => 25],
                    ['name' => 'Trạm trả khách Cửa ngõ Hòa Thuận - Đắk Lắk', 'address' => 'QL14, Xã Hòa Thuận, TP. Buôn Ma Thuột, Đắk Lắk', 'order' => 3, 'dist' => 465, 'mins' => 600],
                    ['name' => 'Bến xe Phía Bắc Buôn Ma Thuột', 'address' => '71 Nguyễn Tất Thành, TP. Buôn Ma Thuột, Đắk Lắk', 'order' => 4, 'dist' => 480, 'mins' => 630],
                ]
            ],
            10 => [
                'stops' => [
                    ['name' => 'Bến xe Phía Bắc Buôn Ma Thuột', 'address' => '71 Nguyễn Tất Thành, TP. Buôn Ma Thuột, Đắk Lắk', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Trung tâm Buôn Ma Thuột', 'address' => 'Văn phòng Ngã Sáu Ban Mê, Lê Duẩn, TP. Buôn Ma Thuột', 'order' => 2, 'dist' => 6, 'mins' => 15],
                    ['name' => 'Trạm trả khách Cửa ngõ Hòa Cầm (Đà Nẵng)', 'address' => 'Nút giao Hòa Cầm, QL1A, Cẩm Lệ, TP. Đà Nẵng', 'order' => 3, 'dist' => 468, 'mins' => 610],
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 4, 'dist' => 480, 'mins' => 630],
                ]
            ],
            11 => [
                'stops' => [
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Trung chuyển Nội thành Đà Nẵng', 'address' => 'Ngã tư Nguyễn Sinh Sắc, Liên Chiểu, TP. Đà Nẵng', 'order' => 2, 'dist' => 8, 'mins' => 20],
                    ['name' => 'Trạm trả khách Cửa ngõ Pháp Vân - Giải Phóng', 'address' => 'Nút giao Pháp Vân - Cầu Giẽ, Hoàng Mai, Hà Nội', 'order' => 3, 'dist' => 745, 'mins' => 750],
                    ['name' => 'Bến xe Nước Ngầm Hà Nội', 'address' => '01 Ngọc Hồi, Hoàng Liệt, Hoàng Mai, Hà Nội', 'order' => 4, 'dist' => 760, 'mins' => 780],
                ]
            ],
            12 => [
                'stops' => [
                    ['name' => 'Bến xe Nước Ngầm Hà Nội', 'address' => '01 Ngọc Hồi, Hoàng Liệt, Hoàng Mai, Hà Nội', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Văn phòng Giải Phóng Hà Nội', 'address' => '809 Giải Phóng, Giáp Bát, Hoàng Mai, Hà Nội', 'order' => 2, 'dist' => 5, 'mins' => 15],
                    ['name' => 'Trạm trả khách Cửa ngõ Ngã ba Huế (Đà Nẵng)', 'address' => 'Cầu vượt Ngã ba Huế, Thanh Khê, TP. Đà Nẵng', 'order' => 3, 'dist' => 752, 'mins' => 760],
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 4, 'dist' => 760, 'mins' => 780],
                ]
            ],
            13 => [
                'stops' => [
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Trung chuyển Nội thành Đà Nẵng', 'address' => 'Ngã tư Nguyễn Sinh Sắc, Liên Chiểu, TP. Đà Nẵng', 'order' => 2, 'dist' => 8, 'mins' => 20],
                    ['name' => 'Trạm trả khách Cửa ngõ Cầu Đò Quan (Nam Định)', 'address' => 'Đường Vũ Hữu Lợi, TP. Nam Định', 'order' => 3, 'dist' => 675, 'mins' => 700],
                    ['name' => 'Bến xe Nam Định', 'address' => 'Trần Đăng Ninh, TP. Nam Định', 'order' => 4, 'dist' => 690, 'mins' => 720],
                ]
            ],
            14 => [
                'stops' => [
                    ['name' => 'Bến xe Nam Định', 'address' => 'Trần Đăng Ninh, TP. Nam Định', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Cửa ngõ Cầu Đò Quan (Nam Định)', 'address' => 'Đường Vũ Hữu Lợi, TP. Nam Định', 'order' => 2, 'dist' => 5, 'mins' => 15],
                    ['name' => 'Trạm trả khách Cửa ngõ Ngã ba Huế (Đà Nẵng)', 'address' => 'Cầu vượt Ngã ba Huế, Thanh Khê, TP. Đà Nẵng', 'order' => 3, 'dist' => 682, 'mins' => 700],
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 4, 'dist' => 690, 'mins' => 720],
                ]
            ],
            15 => [
                'stops' => [
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Cầu Rồng / Trung tâm Đà Nẵng', 'address' => 'Số 02 đường 2/9, Hải Châu, TP. Đà Nẵng', 'order' => 2, 'dist' => 8, 'mins' => 20],
                    ['name' => 'Trạm trả khách Cửa ngõ Điện Bàn - Hội An', 'address' => 'Đường Trần Phú, Điện Bàn, Quảng Nam', 'order' => 3, 'dist' => 25, 'mins' => 40],
                    ['name' => 'Bến xe Hội An', 'address' => 'Huỳnh Thúc Kháng, Cẩm Phô, TP. Hội An', 'order' => 4, 'dist' => 35, 'mins' => 50],
                ]
            ],
            16 => [
                'stops' => [
                    ['name' => 'Bến xe Hội An', 'address' => 'Huỳnh Thúc Kháng, Cẩm Phô, TP. Hội An', 'order' => 1, 'dist' => 0, 'mins' => 0],
                    ['name' => 'Trạm đón Phố cổ Hội An', 'address' => '126 Lý Thường Kiệt, TP. Hội An', 'order' => 2, 'dist' => 4, 'mins' => 10],
                    ['name' => 'Trạm trả khách Cầu Rồng / Trung tâm Đà Nẵng', 'address' => 'Số 02 đường 2/9, Hải Châu, TP. Đà Nẵng', 'order' => 3, 'dist' => 27, 'mins' => 40],
                    ['name' => 'Bến xe Trung tâm Đà Nẵng', 'address' => '185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng', 'order' => 4, 'dist' => 35, 'mins' => 50],
                ]
            ]
        ];

        $now = date('Y-m-d H:i:s');
        $existingRoutes = DB::table('routes')->pluck('id')->toArray();

        foreach ($routesData as $routeId => $routeConfig) {
            if (!in_array($routeId, $existingRoutes)) continue;

            $existingStops = DB::table('route_stops')
                ->where('route_id', $routeId)
                ->orderBy('stop_order', 'asc')
                ->get();

            $newStops = $routeConfig['stops'];

            if ($existingStops->count() >= 4) {
                foreach ($newStops as $idx => $ns) {
                    if (isset($existingStops[$idx])) {
                        DB::table('route_stops')->where('id', $existingStops[$idx]->id)->update([
                            'stop_name' => $ns['name'],
                            'address' => $ns['address'],
                            'stop_order' => $ns['order'],
                            'distance_from_start_km' => $ns['dist'],
                            'estimated_minutes_from_start' => $ns['mins'],
                            'updated_at' => $now,
                        ]);
                    }
                }
            } elseif ($existingStops->count() == 3) {
                // Keep 1 and 3, update mid to 3, insert 2
                DB::table('route_stops')->where('id', $existingStops[0]->id)->update([
                    'stop_name' => $newStops[0]['name'],
                    'address' => $newStops[0]['address'],
                    'stop_order' => 1,
                    'distance_from_start_km' => $newStops[0]['dist'],
                    'estimated_minutes_from_start' => $newStops[0]['mins'],
                    'updated_at' => $now,
                ]);
                DB::table('route_stops')->where('id', $existingStops[2]->id)->update([
                    'stop_name' => $newStops[3]['name'],
                    'address' => $newStops[3]['address'],
                    'stop_order' => 4,
                    'distance_from_start_km' => $newStops[3]['dist'],
                    'estimated_minutes_from_start' => $newStops[3]['mins'],
                    'updated_at' => $now,
                ]);
                DB::table('route_stops')->where('id', $existingStops[1]->id)->update([
                    'stop_name' => $newStops[2]['name'],
                    'address' => $newStops[2]['address'],
                    'stop_order' => 3,
                    'distance_from_start_km' => $newStops[2]['dist'],
                    'estimated_minutes_from_start' => $newStops[2]['mins'],
                    'updated_at' => $now,
                ]);
                DB::table('route_stops')->insert([
                    'route_id' => $routeId,
                    'stop_name' => $newStops[1]['name'],
                    'address' => $newStops[1]['address'],
                    'stop_order' => 2,
                    'distance_from_start_km' => $newStops[1]['dist'],
                    'estimated_minutes_from_start' => $newStops[1]['mins'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif ($existingStops->count() == 2) {
                DB::table('route_stops')->where('id', $existingStops[0]->id)->update([
                    'stop_name' => $newStops[0]['name'],
                    'address' => $newStops[0]['address'],
                    'stop_order' => 1,
                    'distance_from_start_km' => $newStops[0]['dist'],
                    'estimated_minutes_from_start' => $newStops[0]['mins'],
                    'updated_at' => $now,
                ]);
                DB::table('route_stops')->where('id', $existingStops[1]->id)->update([
                    'stop_name' => $newStops[3]['name'],
                    'address' => $newStops[3]['address'],
                    'stop_order' => 4,
                    'distance_from_start_km' => $newStops[3]['dist'],
                    'estimated_minutes_from_start' => $newStops[3]['mins'],
                    'updated_at' => $now,
                ]);
                DB::table('route_stops')->insert([
                    'route_id' => $routeId,
                    'stop_name' => $newStops[1]['name'],
                    'address' => $newStops[1]['address'],
                    'stop_order' => 2,
                    'distance_from_start_km' => $newStops[1]['dist'],
                    'estimated_minutes_from_start' => $newStops[1]['mins'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('route_stops')->insert([
                    'route_id' => $routeId,
                    'stop_name' => $newStops[2]['name'],
                    'address' => $newStops[2]['address'],
                    'stop_order' => 3,
                    'distance_from_start_km' => $newStops[2]['dist'],
                    'estimated_minutes_from_start' => $newStops[2]['mins'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // No need to reverse
    }
};
