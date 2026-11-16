<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Booking;

class EmailService
{
    /**
     * Encode a string as a base64 attachment data-uri for Google Apps Script relay
     * Returns a [name => base64string] array for the webhook's 'attachments' field
     */
    private static function makeAttachmentPayload(string $content, string $filename, string $mimeType = 'text/html'): array
    {
        return [
            'name'     => $filename,
            'mimeType' => $mimeType,
            'content'  => base64_encode($content),
        ];
    }

    /**
     * Resilient HTML Email Sender:
     * 1. Google Apps Script Webhook Relay (Port 443 - works 100% on Render cloud where SMTP port 587 is blocked)
     * 2. Brevo API (if BREVO_API_KEY is configured)
     * 3. Google SMTP (Mail::html fallback for Localhost / VPS)
     */
    public static function sendRawHtml(string $to, string $subject, string $htmlContent, array $attachments = []): bool
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
                $payload = [
                    'to'          => $to,
                    'subject'     => $subject,
                    'html'        => $htmlContent,
                ];
                if (!empty($attachments)) {
                    $payload['attachments'] = $attachments;
                }
                $res = Http::withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept'       => 'application/json',
                ])->timeout(15)->post($webhookUrl, $payload);

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
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept' => 'application/json',
                ])->timeout(8)->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => config('mail.from.name', 'Trường ĐH Kiến Trúc Đà Nẵng - nhaxekientruc'),
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
     * Danh sách email của Quản trị viên / Nhà xe nhận bản lưu PDF
     */
    public static function getAdminEmails(): array
    {
        $emails = [
            env('ADMIN_EMAIL', 'tronghieuvo9@gmail.com'),
            env('MAIL_FROM_ADDRESS', 'volananh2k4@gmail.com'),
            'tronghieuvo9@gmail.com',
            'volananh2k4@gmail.com',
        ];
        return array_values(array_unique(array_filter($emails)));
    }

    /**
     * Send booking confirmation email with ticket code & check-in QR code
     * (Khách hàng nhận email xác nhận & link mở quản lý vé. File PDF được gửi về email Admin lưu trữ)
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
            'ticketUrl' => url('/booking/' . $booking->booking_code . '/detail'),
            'qrToken' => $qrToken,
        ];

        $subject = "[nhaxekientruc] Xác Nhận Đặt Vé #{$booking->booking_code} & Thẻ Lên Xe Điện Tử";
        $htmlContent = view('emails.booking_confirmed', $emailData)->render();

        // 1. Gửi email xác nhận cho KHÁCH HÀNG (không đính kèm PDF theo yêu cầu)
        $sentToCustomer = self::sendRawHtml($to, $subject, $htmlContent, []);

        // 2. Gửi file VÉ PDF về email của ADMIN để Admin tải về máy tính lưu trữ
        try {
            self::sendAdminBookingNotificationWithPdf($booking, $customData);
        } catch (\Throwable $ae) {
            Log::warning('[EMAIL_SERVICE] Could not send booking PDF to Admin: ' . $ae->getMessage());
        }

        return $sentToCustomer;
    }

    /**
     * Gửi email thông báo đơn vé mới đính kèm FILE VÉ PDF về máy của Admin
     */
    public static function sendAdminBookingNotificationWithPdf(Booking $booking, array $customData = []): bool
    {
        $adminEmails = self::getAdminEmails();
        if (empty($adminEmails)) {
            return false;
        }

        $booking->loadMissing(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats']);
        $trip = $booking->trip;
        $seatsStr = $booking->bookingSeats->pluck('seat_number')->filter()->join(', ');
        if (empty($seatsStr) && !empty($customData['seatNumbers'])) {
            $seatsStr = $customData['seatNumbers'];
        }

        // Sinh file đính kèm vé PDF
        $attachments = [];
        try {
            $ticket = \App\Services\PdfTicketService::generateTicketHtml($booking);
            $attachments[] = self::makeAttachmentPayload(
                $ticket['html'],
                $ticket['filename'],
                'text/html'
            );
        } catch (\Throwable $pe) {
            Log::warning('[EMAIL_SERVICE_ADMIN] Ticket attachment error: ' . $pe->getMessage());
        }

        $subject = "[ADMIN NHAXEKIENTRUC] Lưu Trữ File Vé PDF #{$booking->booking_code} - Khách {$booking->customer_name} ({$booking->customer_phone})";
        
        $totalFormatted = number_format((float)($booking->total_amount ?? 0), 0, ',', '.') . ' VNĐ';
        $paidFormatted = number_format((float)($booking->paid_amount ?? 0), 0, ',', '.') . ' VNĐ';
        $routeName = $trip?->route?->name ?? 'Tuyến xe';
        $depTime = $trip?->departure_time ? \Carbon\Carbon::parse($trip->departure_time)->format('H:i - d/m/Y') : 'Xem trên vé';
        $adminTicketUrl = url('/booking/' . $booking->booking_code . '/detail');

        $htmlContent = '
        <div style="font-family: Arial, sans-serif; max-width: 650px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden;">
            <div style="background: #b91c1c; color: #ffffff; padding: 20px 24px;">
                <h2 style="margin: 0; font-size: 18px; text-transform: uppercase;">[ADMIN] THÔNG BÁO ĐƠN VÉ MỚI & FILE VÉ PDF LƯU TRỮ</h2>
                <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.9;">Mã Đặt Vé: <strong>#' . $booking->booking_code . '</strong> &bull; ' . now()->format('H:i - d/m/Y') . '</p>
            </div>
            <div style="padding: 24px;">
                <div style="background: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px;">
                    <strong style="color: #1e40af; font-size: 13px;">&#128196; FILE VÉ PDF ĐÍNH KÈM:</strong>
                    <p style="margin: 6px 0 0 0; font-size: 12px; color: #1e3a8a; line-height: 1.5;">
                        Hệ thống đã đính kèm bản in vé điện tử <strong>ve-' . strtolower($booking->booking_code) . '.html (PDF)</strong> trong email này để Admin tải về máy tính lưu trữ và đối soát danh sách hành khách.
                    </p>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr><td style="padding: 8px 0; color: #64748b; width: 35%;">Hành khách:</td><td style="padding: 8px 0; font-weight: bold; color: #0f172a;">' . htmlspecialchars($booking->customer_name) . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Số điện thoại:</td><td style="padding: 8px 0; font-weight: bold; color: #b91c1c; font-family: monospace;">' . htmlspecialchars($booking->customer_phone) . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Email khách:</td><td style="padding: 8px 0; color: #0f172a;">' . htmlspecialchars($booking->customer_email) . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Tuyến xe:</td><td style="padding: 8px 0; font-weight: bold; color: #0f172a;">' . htmlspecialchars($routeName) . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Khởi hành:</td><td style="padding: 8px 0; font-weight: bold; color: #b91c1c;">' . $depTime . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Ghế ngồi:</td><td style="padding: 8px 0; font-weight: bold; color: #7c3aed; font-family: monospace;">' . htmlspecialchars($seatsStr ?: 'Chỗ online') . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Tổng tiền:</td><td style="padding: 8px 0; font-weight: bold; color: #0f172a;">' . $totalFormatted . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Đã thanh toán:</td><td style="padding: 8px 0; font-weight: bold; color: #059669;">' . $paidFormatted . ' (' . $booking->payment_status . ')</td></tr>
                </table>
                <div style="margin-top: 24px; text-align: center;">
                    <a href="' . $adminTicketUrl . '" style="display: inline-block; background: #b91c1c; color: #ffffff; padding: 12px 24px; border-radius: 10px; font-weight: bold; text-decoration: none; font-size: 13px;">
                        XEM VÉ TRÊN HỆ THỐNG QUẢN LÝ &rarr;
                    </a>
                </div>
            </div>
            <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 24px; font-size: 11px; color: #94a3b8; text-align: center;">
                Trường Đại Học Kiến Trúc Đà Nẵng &bull; nhaxekientruc Terminal System
            </div>
        </div>';

        $allSent = true;
        foreach ($adminEmails as $adminEmail) {
            $ok = self::sendRawHtml($adminEmail, $subject, $htmlContent, $attachments);
            if (!$ok) $allSent = false;
        }

        return $allSent;
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

        $subject = "[nhaxekientruc] Nhắc nhở xuất bến trước 45 phút - Chuyến " . ($trip?->trip_code ?? '') . " (" . ($trip?->route?->name ?? 'Tuyến xe') . ")";
        $htmlContent = view('emails.departure_reminder_45m', $emailData)->render();

        $sent = self::sendRawHtml($to, $subject, $htmlContent);

        // Ghi lại tracking: AI tự động hay Admin bấm tay
        if ($sent) {
            $booking->reminder_sent_at = now();
            $booking->reminder_sent_by = $booking->reminder_sent_by ?? 'auto'; // default auto, admin có thể override
            $booking->save();
        }

        return $sent;
    }

    /**
     * Gửi nhắc nhở 45 phút do ADMIN bấm tay (ghi nhận sent_by = 'manual')
     */
    public static function sendDepartureReminderManual(Booking $booking): bool
    {
        // Tạm thời set manual để sendDepartureReminder ghi vào DB
        $booking->reminder_sent_by = 'manual';
        return self::sendDepartureReminder($booking);
    }

    /**
     * Send refund approved email
     */
    public static function sendRefundApproved(Booking $booking, array $refundData = []): bool
    {
        $to = $booking->customer_email ?: ($booking->user?->email ?: null);

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
            'ticketUrl' => url('/booking/' . $booking->booking_code . '/detail'),
        ];

        $subject = "[nhaxekientruc] Đã Duyệt Hoàn Tiền Vé #{$booking->booking_code}";
        $htmlContent = view('emails.refund_approved', $emailData)->render();

        $sentCustomer = false;
        if (!empty($to) && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $sentCustomer = self::sendRawHtml($to, $subject, $htmlContent);
        }

        // Tự động gửi file PDF biên nhận hoàn tiền về email Admin lưu trữ
        try {
            self::sendAdminRefundNotificationWithPdf($booking, $refundData);
        } catch (\Throwable $re) {
            Log::warning('[EMAIL_SERVICE] Could not send refund PDF to Admin: ' . $re->getMessage());
        }

        return $sentCustomer;
    }

    /**
     * Gửi biên nhận HỦY VÉ & HOÀN TIỀN PDF về email máy của Admin lưu trữ
     */
    public static function sendAdminRefundNotificationWithPdf(Booking $booking, array $refundData = []): bool
    {
        $adminEmails = self::getAdminEmails();
        if (empty($adminEmails)) {
            return false;
        }

        $booking->loadMissing(['trip.route']);
        $trip = $booking->trip;

        // Sinh file đính kèm Biên nhận hoàn tiền PDF
        $attachments = [];
        try {
            $receipt = \App\Services\PdfTicketService::generateRefundReceiptHtml($booking, $refundData);
            $attachments[] = self::makeAttachmentPayload(
                $receipt['html'],
                $receipt['filename'],
                'text/html'
            );
        } catch (\Throwable $pe) {
            Log::warning('[EMAIL_SERVICE_ADMIN] Refund receipt attachment error: ' . $pe->getMessage());
        }

        $subject = "[ADMIN NHAXEKIENTRUC] Lưu Trữ File PDF Biên Nhận Hoàn Tiền Vé #{$booking->booking_code}";
        $refundAmountFormatted = number_format((float)($refundData['refundAmount'] ?? $booking->refund_amount ?? 0), 0, ',', '.') . ' VNĐ';
        $bankName = $refundData['bankName'] ?? ($booking->refund_bank_name ?: 'Ngân hàng');
        $accNum = $refundData['accountNumber'] ?? ($booking->refund_account_number ?: 'Chính chủ');
        $accName = $refundData['accountName'] ?? ($booking->refund_account_name ?: $booking->customer_name);
        $txCode = $refundData['txCode'] ?? ('REF-' . time());

        $htmlContent = '
        <div style="font-family: Arial, sans-serif; max-width: 650px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden;">
            <div style="background: #047857; color: #ffffff; padding: 20px 24px;">
                <h2 style="margin: 0; font-size: 18px; text-transform: uppercase;">[ADMIN] BIÊN NHẬN HỦY VÉ & HOÀN TIỀN THÀNH CÔNG (FILE PDF)</h2>
                <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.9;">Mã Vé: <strong>#' . $booking->booking_code . '</strong> &bull; GD: #' . $txCode . ' &bull; ' . now()->format('H:i - d/m/Y') . '</p>
            </div>
            <div style="padding: 24px;">
                <div style="background: #ecfdf5; border: 1.5px solid #a7f3d0; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px;">
                    <strong style="color: #065f46; font-size: 13px;">&#128196; FILE BIÊN NHẬN PDF ĐÍNH KÈM:</strong>
                    <p style="margin: 6px 0 0 0; font-size: 12px; color: #047857; line-height: 1.5;">
                        Hệ thống đã đính kèm file biên nhận hoàn tiền <strong>bien-nhan-hoan-tien-' . strtolower($booking->booking_code) . '.html (PDF)</strong> trong email này để Admin lưu về máy tính.
                    </p>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr><td style="padding: 8px 0; color: #64748b; width: 35%;">Hành khách:</td><td style="padding: 8px 0; font-weight: bold; color: #0f172a;">' . htmlspecialchars($booking->customer_name) . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Số điện thoại:</td><td style="padding: 8px 0; font-weight: bold; color: #b91c1c; font-family: monospace;">' . htmlspecialchars($booking->customer_phone) . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Số tiền hoàn:</td><td style="padding: 8px 0; font-weight: bold; color: #059669; font-size: 15px; font-family: monospace;">' . $refundAmountFormatted . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Ngân hàng thụ hưởng:</td><td style="padding: 8px 0; font-weight: bold; color: #0f172a;">' . htmlspecialchars($bankName) . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">STK nhận tiền:</td><td style="padding: 8px 0; font-weight: bold; color: #b91c1c; font-family: monospace;">' . htmlspecialchars($accNum) . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Chủ tài khoản:</td><td style="padding: 8px 0; font-weight: bold; color: #0f172a; text-transform: uppercase;">' . htmlspecialchars($accName) . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Mã giao dịch:</td><td style="padding: 8px 0; font-weight: bold; color: #4338ca; font-family: monospace;">' . htmlspecialchars($txCode) . '</td></tr>
                    <tr><td style="padding: 8px 0; color: #64748b;">Thời gian duyệt:</td><td style="padding: 8px 0; color: #0f172a;">' . now()->format('H:i - d/m/Y') . '</td></tr>
                </table>
            </div>
            <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 24px; font-size: 11px; color: #94a3b8; text-align: center;">
                Trường Đại Học Kiến Trúc Đà Nẵng &bull; nhaxekientruc Terminal System
            </div>
        </div>';

        $allSent = true;
        foreach ($adminEmails as $adminEmail) {
            $ok = self::sendRawHtml($adminEmail, $subject, $htmlContent, $attachments);
            if (!$ok) $allSent = false;
        }

        return $allSent;
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

        $subject = "[nhaxekientruc] Thông Báo Từ Chối Yêu Cầu Hoàn Tiền Vé #{$booking->booking_code}";
        $htmlContent = view('emails.refund_rejected', $emailData)->render();

        return self::sendRawHtml($to, $subject, $htmlContent);
    }
}
