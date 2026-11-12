<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Booking;

class EmailService
{
    /**
     * Resilient HTML Email Sender:
     * 1. Google Apps Script Webhook Relay (Port 443 - works 100% on Render cloud where SMTP port 587 is blocked)
     * 2. Brevo API (if BREVO_API_KEY is configured)
     * 3. Google SMTP (Mail::html fallback for Localhost / VPS)
     */
    public static function sendRawHtml(string $to, string $subject, string $htmlContent): bool
    {
        $to = trim($to);
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Log::warning("[EMAIL_SERVICE] Invalid recipient email: '{$to}'");
            return false;
        }

        $sent = false;

        // 1. Google Apps Script Webhook Relay (Port 443 - HTTPS)
        $webhookUrl = env('EMAIL_WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbzApD7OrDIllIWztGiO_Ory8m-eYOYLCvjUclzeThdOhz4pWTf8fUgPU67vuYIhwvCI/exec');
        if (!empty($webhookUrl)) {
            try {
                $res = Http::timeout(10)->post($webhookUrl, [
                    'to' => $to,
                    'subject' => $subject,
                    'html' => $htmlContent,
                ]);

                if ($res->successful()) {
                    $json = $res->json();
                    if (!isset($json['success']) || $json['success'] === true) {
                        $sent = true;
                        Log::info("[EMAIL_SERVICE_WEBHOOK_SUCCESS] Sent email to {$to} via Google Apps Script Relay. Subject: {$subject}");
                        return true;
                    }
                }
                Log::warning("[EMAIL_SERVICE_WEBHOOK_FAIL] Webhook returned status " . $res->status() . ": " . $res->body());
            } catch (\Throwable $e) {
                Log::warning("[EMAIL_SERVICE_WEBHOOK_ERROR] " . $e->getMessage());
            }
        }

        // 2. Brevo HTTPS API (if configured)
        $brevoKey = env('BREVO_API_KEY');
        if (!$sent && !empty($brevoKey)) {
            try {
                $res = Http::withHeaders([
                    'api-key' => $brevoKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(8)->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => config('mail.from.name', 'nhaxekientruc Terminal'),
                        'email' => config('mail.from.address', 'noreply@nhaxekientruc.vn')
                    ],
                    'to' => [['email' => $to]],
                    'subject' => $subject,
                    'htmlContent' => $htmlContent,
                ]);
                if ($res->successful()) {
                    Log::info("[EMAIL_SERVICE_BREVO_SUCCESS] Sent email to {$to} via Brevo API");
                    return true;
                }
            } catch (\Throwable $be) {
                Log::warning("[EMAIL_SERVICE_BREVO_ERROR] " . $be->getMessage());
            }
        }

        // 3. Google SMTP Mail::html fallback
        try {
            Mail::html($htmlContent, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
            Log::info("[EMAIL_SERVICE_SMTP_SUCCESS] Sent email to {$to} via SMTP");
            return true;
        } catch (\Throwable $e) {
            Log::error("[EMAIL_SERVICE_SMTP_ERROR] Failed to send email via SMTP to {$to}: " . $e->getMessage());
        }

        return $sent;
    }

    /**
     * Send booking confirmation email with ticket code & check-in QR code
     */
    public static function sendBookingConfirmation(Booking $booking, array $customData = []): bool
    {
        $to = $booking->customer_email;
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $booking->loadMissing(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats']);

        $trip = $booking->trip;
        $seatsStr = $booking->bookingSeats->pluck('seat_number')->filter()->join(', ');
        if (empty($seatsStr) && !empty($customData['seatNumbers'])) {
            $seatsStr = $customData['seatNumbers'];
        }

        $qrToken = $booking->qr_token ?: $booking->booking_code;

        $emailData = [
            'customerName' => $booking->customer_name,
            'bookingCode' => $booking->booking_code,
            'routeName' => $trip?->route?->name ?? 'Tuyến xe nhaxekientruc',
            'departureTime' => $trip?->departure_time ? \Carbon\Carbon::parse($trip->departure_time)->format('H:i - d/m/Y') : 'Xem trên vé',
            'pickupStop' => $customData['pickupStop'] ?? ($booking->pickupStop?->stop_name ?? 'Bến xe xuất phát'),
            'dropoffStop' => $customData['dropoffStop'] ?? ($booking->dropoffStop?->stop_name ?? 'Bến xe đến'),
            'seatNumbers' => $seatsStr ?: 'Chỗ đặt trực tuyến',
            'totalAmount' => number_format((float)($customData['totalAmount'] ?? $booking->total_amount ?? $booking->total_price ?? 0)) . ' VNĐ',
            'paidAmount' => number_format((float)($customData['paidAmount'] ?? $booking->paid_amount ?? 0)) . ' VNĐ',
            'busPlate' => $trip?->bus?->license_plate ?? '43B-045.67',
            'busType' => $trip?->bus?->bus_type ?? 'Giường nằm VIP',
            'driverName' => $trip?->driver?->name ?? 'Bác tài nhaxekientruc',
            'ticketUrl' => url('/ve/' . $booking->booking_code),
            'qrToken' => $qrToken,
        ];

        $subject = "🎫 [nhaxekientruc] Xác Nhận Đặt Vé #{$booking->booking_code} & Mã QR Check-in";
        $htmlContent = view('emails.booking_confirmed', $emailData)->render();

        return self::sendRawHtml($to, $subject, $htmlContent);
    }

    /**
     * Send AI departure reminder (45 mins before departure)
     */
    public static function sendDepartureReminder(Booking $booking): bool
    {
        $to = $booking->customer_email ?: ($booking->user?->email ?: null);
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Log::warning("[EMAIL_SERVICE] Cannot send reminder: No valid email for booking #{$booking->booking_code}");
            return false;
        }

        $booking->loadMissing(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats', 'user']);

        $trip = $booking->trip;
        $seatsStr = $booking->bookingSeats->pluck('seat_number')->filter()->join(', ');

        $emailData = [
            'customerName' => $booking->customer_name ?: ($booking->user?->name ?? 'Quý Khách'),
            'bookingCode' => $booking->booking_code,
            'tripCode' => $trip?->trip_code ?? 'TRIP',
            'routeName' => $trip?->route?->name ?? 'Tuyến xe nhaxekientruc',
            'departureTime' => $trip?->departure_time ? \Carbon\Carbon::parse($trip->departure_time)->format('H:i - d/m/Y') : 'Xem trên vé',
            'pickupStop' => $booking->pickupStop?->stop_name ?? ($trip?->pickup_point ?? 'Bến xe Trung tâm Đà Nẵng'),
            'seatNumbers' => $seatsStr ?: ($booking->seat_code ?: 'Chỗ đặt trực tuyến'),
            'busPlate' => $trip?->bus?->license_plate ?? '43B-012.34',
            'busType' => $trip?->bus?->bus_type ?? 'Xe Giường Nằm VIP',
            'driverName' => $trip?->driver?->name ?? 'Bác tài nhaxekientruc',
            'driverPhone' => $trip?->driver?->phone ?? '0905.123.456',
            'ticketUrl' => url('/booking/' . $booking->booking_code),
        ];

        $subject = "⏰ [nhaxekientruc] Nhắc nhở xuất bến trước 45 phút - Chuyến " . ($trip?->trip_code ?? '') . " (" . ($trip?->route?->name ?? 'Tuyến xe') . ")";
        $htmlContent = view('emails.departure_reminder_45m', $emailData)->render();

        return self::sendRawHtml($to, $subject, $htmlContent);
    }

    /**
     * Send refund approved email
     */
    public static function sendRefundApproved(Booking $booking, array $refundData = []): bool
    {
        $to = $booking->customer_email ?: ($booking->user?->email ?: null);
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $booking->loadMissing(['trip.route']);
        $trip = $booking->trip;

        $emailData = [
            'customerName' => $booking->customer_name ?: 'Quý Khách',
            'bookingCode' => $booking->booking_code,
            'routeName' => $trip?->route?->name ?? 'Tuyến xe nhaxekientruc',
            'refundAmount' => number_format((float)($refundData['refundAmount'] ?? $booking->refund_amount ?? 0), 0, ',', '.') . ' VNĐ',
            'paymentMethod' => $refundData['paymentMethod'] ?? ($booking->refund_bank_name ? 'Chuyển khoản Ngân hàng' : 'Ví điện tử'),
            'bankName' => $refundData['bankName'] ?? ($booking->refund_bank_name ?: 'Chuyển khoản theo yêu cầu'),
            'accountNumber' => $refundData['accountNumber'] ?? ($booking->refund_account_number ?: 'Tài khoản gốc'),
            'accountName' => $refundData['accountName'] ?? ($booking->refund_account_name ?: $booking->customer_name),
            'txCode' => $refundData['txCode'] ?? '',
            'adminNotes' => $refundData['adminNotes'] ?? '',
            'processedAt' => now()->format('H:i - d/m/Y'),
            'ticketUrl' => url('/booking/' . $booking->booking_code),
        ];

        $subject = "✅ [nhaxekientruc] Đã Duyệt Hoàn Tiền Vé #{$booking->booking_code}";
        $htmlContent = view('emails.refund_approved', $emailData)->render();

        return self::sendRawHtml($to, $subject, $htmlContent);
    }

    /**
     * Send refund rejected email
     */
    public static function sendRefundRejected(Booking $booking, string $reason): bool
    {
        $to = $booking->customer_email ?: ($booking->user?->email ?: null);
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $booking->loadMissing(['trip.route']);
        $trip = $booking->trip;

        $emailData = [
            'customerName' => $booking->customer_name ?: 'Quý Khách',
            'bookingCode' => $booking->booking_code,
            'routeName' => $trip?->route?->name ?? 'Tuyến xe nhaxekientruc',
            'rejectReason' => $reason,
            'departureTime' => $trip?->departure_time ? \Carbon\Carbon::parse($trip->departure_time)->format('H:i - d/m/Y') : 'Xem trên vé',
            'processedAt' => now()->format('H:i - d/m/Y'),
            'ticketUrl' => url('/booking/' . $booking->booking_code),
        ];

        $subject = "⚠️ [nhaxekientruc] Thông Báo Từ Chối Yêu Cầu Hoàn Tiền Vé #{$booking->booking_code}";
        $htmlContent = view('emails.refund_rejected', $emailData)->render();

        return self::sendRawHtml($to, $subject, $htmlContent);
    }
}
