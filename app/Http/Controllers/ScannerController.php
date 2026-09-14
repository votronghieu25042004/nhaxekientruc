<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Booking;
use Carbon\Carbon;

class ScannerController extends Controller
{
    public function index()
    {
        return Inertia::render('Scanner/Index');
    }

    public function verify(Request $request)
    {
        $token = $request->input('qr_token');
        if (!$token) {
            return response()->json(['valid' => false, 'message' => 'Mã QR không hợp lệ.'], 400);
        }

        $booking = Booking::with(['trip.route.departureStation', 'trip.route.arrivalStation', 'trip.bus', 'trip.company', 'bookingSeats'])
            ->where('qr_token', $token)
            ->orWhere('booking_code', $token)
            ->first();

        if (!$booking) {
            return response()->json([
                'valid' => false,
                'message' => 'Vé không tồn tại trong hệ thống hoặc mã QR giả mạo!',
            ]);
        }

        return response()->json([
            'valid' => true,
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'seats' => $booking->bookingSeats->pluck('seat_number')->toArray(),
                'total_amount' => (float) $booking->total_amount,
                'deposit_amount' => (float) $booking->deposit_amount,
                'remaining_amount' => (float) $booking->remaining_amount,
                'payment_status' => $booking->payment_status,
                'checkin_status' => $booking->checkin_status,
                'checked_in_at' => $booking->checked_in_at ? $booking->checked_in_at->format('H:i - d/m/Y') : null,
                'departure_time' => $booking->trip->departure_time->format('H:i - d/m/Y'),
                'from_station' => $booking->trip->route->departureStation->name,
                'to_station' => $booking->trip->route->arrivalStation->name,
                'company_name' => $booking->trip->company->name,
                'license_plate' => $booking->trip->bus->license_plate,
                'bus_type' => $booking->trip->bus->bus_type,
            ]
        ]);
    }

    public function checkin(Request $request)
    {
        $bookingId = $request->input('booking_id');
        $staffName = $request->input('staff_name', 'Nhân viên soát vé quầy');

        $booking = Booking::findOrFail($bookingId);

        if ($booking->checkin_status === 'boarded') {
            return response()->json([
                'success' => false,
                'message' => 'CẢNH BÁO: Vé này đã được check-in lúc ' . ($booking->checked_in_at ? $booking->checked_in_at->format('H:i d/m/Y') : '') . '!',
            ]);
        }

        $booking->update([
            'payment_status' => 'fully_paid',
            'checkin_status' => 'boarded',
            'checked_in_at' => Carbon::now(),
            'checked_in_by' => $staffName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Xác nhận thu ' . number_format($booking->remaining_amount, 0, ',', '.') . 'đ và Check-in cho khách lên xe THÀNH CÔNG!',
            'checked_in_at' => Carbon::now()->format('H:i - d/m/Y'),
        ]);
    }
}
