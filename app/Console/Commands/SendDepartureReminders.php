<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDepartureReminders extends Command
{
    /**
     * Tên lệnh Artisan: php artisan bus:send-reminders
     */
    protected $signature = 'bus:send-reminders';

    /**
     * Mô tả lệnh
     */
    protected $description = 'Tự động quét và gửi email nhắc nhở trước 45 phút cho các chuyến xe sắp khởi hành';

    public function handle()
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $windowStart = $now->copy()->addMinutes(25);
        $windowEnd = $now->copy()->addMinutes(60);

        $this->info("Đang quét các chuyến xe khởi hành từ {$windowStart->format('H:i')} đến {$windowEnd->format('H:i')}...");

        $trips = Trip::with(['bookings.bookingSeats', 'bookings.user', 'bookings.pickupStop', 'route', 'bus', 'driver'])
            ->whereIn('status', ['SCHEDULED', 'RUNNING', 'READY'])
            ->whereBetween('departure_time', [$windowStart, $windowEnd])
            ->get();

        $totalSent = 0;
        foreach ($trips as $trip) {
            $validBookings = $trip->bookings->whereNotIn('payment_status', ['CANCELLED', 'REFUNDED']);
            $plate = $trip->bus ? ($trip->bus->license_plate ?? '43B-012.34') : '43B-012.34';
            $busType = $trip->bus ? ($trip->bus->bus_type ?? 'Xe Giường Nằm VIP') : 'Xe Giường Nằm VIP';
            $driverName = $trip->driver ? $trip->driver->name : 'Bác tài nhaxekientruc';
            $driverPhone = $trip->driver ? ($trip->driver->phone ?? '0905.123.456') : '0905.123.456';
            $depDate = Carbon::parse($trip->departure_time)->format('H:i - d/m/Y');
            $routeName = $trip->route?->name ?? 'Tuyến đường';

            foreach ($validBookings as $b) {
                $recipient = $b->customer_email ?: ($b->user?->email ?: null);
                if ($recipient && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    try {
                        $seatCodes = $b->bookingSeats->pluck('seat_number')->join(', ') ?: ($b->seat_code ?: 'Ghế');
                        $pickupName = $b->pickupStop?->stop_name ?? ($trip->pickup_point ?? 'Bến xe Trung tâm Đà Nẵng');

                        Mail::send('emails.departure_reminder_45m', [
                            'customerName' => $b->customer_name ?: 'Quý Khách',
                            'tripCode' => $trip->trip_code,
                            'routeName' => $routeName,
                            'departureTime' => $depDate,
                            'busPlate' => $plate,
                            'busType' => $busType,
                            'driverName' => $driverName,
                            'driverPhone' => $driverPhone,
                            'pickupStop' => $pickupName,
                            'seatNumbers' => $seatCodes,
                            'bookingCode' => $b->booking_code,
                            'ticketUrl' => url('/booking/' . $b->booking_code),
                        ], function ($message) use ($recipient, $trip, $routeName) {
                            $message->to($recipient)
                                    ->subject("⏰ [BusHub] Nhắc nhở xuất bến trước 45 phút - Chuyến {$trip->trip_code} ({$routeName})");
                        });

                        Log::info("🔔 [AUTO-REMINDER 45P] Đã gửi email nhắc nhở cho khách {$b->customer_name} ({$recipient}) - Vé {$b->booking_code}");
                        $totalSent++;
                    } catch (\Throwable $e) {
                        Log::warning("Lỗi gửi email reminder trong command cho {$recipient}: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info("✓ Đã gửi email nhắc nhở 45 phút thành công tới {$totalSent} hành khách!");
        return Command::SUCCESS;
    }
}
