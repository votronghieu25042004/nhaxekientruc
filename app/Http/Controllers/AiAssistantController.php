<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\Province;
use App\Models\BusStation;
use Carbon\Carbon;

class AiAssistantController extends Controller
{
    public function chat(Request $request)
    {
        $rawMessage = $request->input('message', '');
        $message = mb_strtolower(trim($rawMessage), 'UTF-8');

        if (empty($message)) {
            return response()->json([
                'reply' => 'Chào bạn! Tôi là Trợ lý ảo AI của BusHub. Bạn muốn tìm xe đi tỉnh/bến xe nào và vào thời gian nào ạ? (Ví dụ: "Tìm xe từ Huế đi Quảng Trị chiều mai")',
                'trips' => [],
            ]);
        }

        // Natural Language Intent Extractor with Vietnamese support
        $fromName = null;
        $toName = null;

        if (preg_match('/(đà nẵng|da nang)/ui', $message)) {
            if (preg_match('/(từ đà nẵng|ở đà nẵng|tai da nang|tu da nang)/ui', $message)) {
                $fromName = 'Đà Nẵng';
            } else {
                $toName = 'Đà Nẵng';
            }
        }

        if (preg_match('/(quảng trị|đông hà|quang tri|dong ha)/ui', $message)) {
            if (preg_match('/(từ quảng trị|ở quảng trị|tu quang tri)/ui', $message)) {
                $fromName = 'Quảng Trị';
            } else {
                $toName = 'Quảng Trị';
            }
        }

        if (preg_match('/(huế|hue)/ui', $message)) {
            if (preg_match('/(từ huế|ở huế|tu hue)/ui', $message)) {
                $fromName = 'Thừa Thiên Huế';
            } else {
                $toName = 'Thừa Thiên Huế';
            }
        }

        if (preg_match('/(hà nội|ha noi)/ui', $message)) {
            if (preg_match('/(từ hà nội|tu ha noi)/ui', $message)) $fromName = 'Hà Nội';
            else $toName = 'Hà Nội';
        }

        if (preg_match('/(sài gòn|hồ chí minh|tphcm|sai gon|ho chi minh)/ui', $message)) {
            if (preg_match('/(từ sài gòn|từ tphcm|tu sai gon)/ui', $message)) $fromName = 'Hồ Chí Minh';
            else $toName = 'Hồ Chí Minh';
        }

        // Default if user just searches a destination
        if (!$fromName && !$toName) {
            $fromName = 'Đà Nẵng';
            $toName = 'Quảng Trị';
        }

        // Query Database
        $query = Trip::with(['route.departureStation.province', 'route.arrivalStation.province', 'bus', 'company', 'seats']);

        if ($fromName) {
            $query->whereHas('route.departureStation.province', fn($q) => $q->where('name', 'like', "%{$fromName}%"));
        }
        if ($toName) {
            $query->whereHas('route.arrivalStation.province', fn($q) => $q->where('name', 'like', "%{$toName}%"));
        }

        $trips = $query->take(4)->get()->map(function ($t) {
            return [
                'id' => $t->id,
                'departure_time' => $t->departure_time->format('H:i'),
                'departure_date' => $t->departure_time->format('d/m/Y'),
                'price' => (float) $t->price,
                'deposit_30' => (float) ($t->price * 0.3),
                'company_name' => $t->company->name,
                'bus_type' => $t->bus->bus_type,
                'from' => $t->route->departureStation->name,
                'to' => $t->route->arrivalStation->name,
                'available_seats' => $t->seats->where('status', 'available')->count(),
            ];
        });

        $count = count($trips);
        if ($count > 0) {
            $originText = $fromName ?: 'Bến xuất phát';
            $destText = $toName ?: 'Điểm đến';
            $reply = "Dạ, tôi đã tìm thấy {$count} chuyến xe từ {$originText} đi {$destText} có sẵn chỗ và hỗ trợ cọc 30%:";
        } else {
            $reply = "Tôi chưa tìm thấy chuyến xe chính xác theo yêu cầu. Bạn có thể thử tìm tuyến phổ biến như Đà Nẵng - Quảng Trị hoặc Huế - Quảng Trị nhé!";
        }

        return response()->json([
            'reply' => $reply,
            'trips' => $trips,
        ]);
    }
}
