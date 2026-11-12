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
        $token = $request->input('qr_token') ?? $request->input('code') ?? '';
        if (!$token) {
            return response()->json(['valid' => false, 'message' => 'Vui lòng cung cấp mã QR hoặc mã vé để kiểm tra.'], 400);
        }

        $booking = Booking::findByQrOrCode($token, null, ['trip.route.stops', 'trip.bus', 'trip.driver', 'bookingSeats']);

        if (!$booking) {
            return response()->json([
                'valid' => false,
                'message' => 'Vé không tồn tại trong hệ thống hoặc mã QR giả mạo!',
            ]);
        }

        $originName = $booking->trip?->route?->origin ?? 'Đà Nẵng';
        $destName = $booking->trip?->route?->destination ?? 'Điểm đến';
        $firstStop = $booking->trip?->route?->stops?->first()?->stop_name ?? 'Bến xe trung tâm';
        $lastStop = $booking->trip?->route?->stops?->last()?->stop_name ?? 'Bến xe trả khách';

        $totalAmt = (float) $booking->total_amount;
        $paidAmt = (float) ($booking->paid_amount ?: 0);
        $remainingAmt = max(0, $totalAmt - $paidAmt);

        return response()->json([
            'valid' => true,
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'seats' => $booking->bookingSeats->pluck('seat_number')->toArray(),
                'total_amount' => $totalAmt,
                'deposit_amount' => $paidAmt,
                'remaining_amount' => $remainingAmt,
                'payment_status' => $booking->payment_status,
                'checkin_status' => $booking->checkin_status,
                'checked_in_at' => $booking->checked_in_at ? $booking->checked_in_at->format('H:i - d/m/Y') : null,
                'departure_time' => $booking->trip?->departure_time ? $booking->trip->departure_time->format('H:i - d/m/Y') : '',
                'from_station' => "{$originName} ({$firstStop})",
                'to_station' => "{$destName} ({$lastStop})",
                'company_name' => 'Nhà xe Kiến Trúc (nhaxekientruc)',
                'license_plate' => $booking->trip?->bus?->license_plate ?? '43B-012.34',
                'bus_type' => $booking->trip?->bus?->bus_type ?? 'Ghế ngồi cao cấp VIP',
            ]
        ]);
    }

    public function checkin(Request $request)
    {
        $bookingId = $request->input('booking_id');
        $staffName = $request->input('staff_name', 'Nhân viên soát vé quầy');

        $booking = Booking::findOrFail($bookingId);

        if ($booking->checkin_status === 'CHECKED_IN' || $booking->checkin_status === 'boarded') {
            return response()->json([
                'success' => false,
                'message' => 'CẢNH BÁO: Vé này đã được check-in lúc ' . ($booking->checked_in_at ? $booking->checked_in_at->format('H:i d/m/Y') : '') . '!',
            ]);
        }

        $remainingAmt = max(0, (float)$booking->total_amount - (float)($booking->paid_amount ?: 0));

        $booking->update([
            'payment_status' => 'PAID',
            'paid_amount' => $booking->total_amount,
            'checkin_status' => 'CHECKED_IN',
            'checkin_method' => 'QR_SCAN',
            'checked_in_at' => Carbon::now(),
            'checked_in_by' => $staffName,
        ]);

        return response()->json([
            'success' => true,
            'message' => $remainingAmt > 0
                ? 'Xác nhận thu ' . number_format($remainingAmt, 0, ',', '.') . 'đ và Check-in cho khách lên xe THÀNH CÔNG!'
                : 'Check-in cho khách lên xe THÀNH CÔNG!',
            'checked_in_at' => Carbon::now()->format('H:i - d/m/Y'),
        ]);
    }
}
