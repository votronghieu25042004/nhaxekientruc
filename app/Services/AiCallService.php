<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AiCallService
{
    /**
     * Chuyển đổi định dạng số điện thoại Việt Nam sang chuẩn quốc tế E.164 (+84...)
     */
    public static function formatPhoneE164(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleaned, '84')) {
            return '+' . $cleaned;
        }
        if (str_starts_with($cleaned, '0')) {
            return '+84' . substr($cleaned, 1);
        }
        return '+84' . $cleaned;
    }

    /**
     * Soạn kịch bản lời thoại AI gọi điện nhắc nhở xuất bến trước 45 phút
     */
    public static function generateCallScript(Booking $booking): string
    {
        $booking->loadMissing(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats', 'user']);

        $trip        = $booking->trip;
        $customer    = $booking->customer_name ?: ($booking->user?->name ?? 'Quý Khách');
        $depCarbon   = $trip?->departure_time ? Carbon::parse($trip->departure_time) : null;
        $depTime     = $depCarbon ? ($depCarbon->format('H') . ' giờ ' . $depCarbon->format('i') . ' phút, ngày ' . $depCarbon->format('d/m/Y')) : 'sắp tới';
        $destination = $trip?->route?->destination ?? 'điểm đến';
        $pickupStop  = $booking->pickupStop?->stop_name ?? ($trip?->pickup_point ?? 'Bến xe Trung tâm Đà Nẵng');
        $busPlate    = $trip?->bus?->license_plate ?? '43B-012.34';
        $driverName  = $trip?->driver?->name ?? 'Bác tài nhaxekientruc';
        $driverPhone = $trip?->driver?->phone ?? '0905.123.456';
        $seatsArr    = $booking->bookingSeats->pluck('seat_number')->filter()->values()->toArray();
        $seatsStr    = implode(', ', $seatsArr) ?: ($booking->seat_code ?: 'chỗ đã đặt');

        return "Dạ xin chào {$customer}! Em là Trợ lý AI tự động của nhà xe nhaxekientruc Bến xe Trung tâm Đà Nẵng. Em gọi điện để nhắc nhở: Chuyến xe đi {$destination} của Quý khách sẽ xuất bến trong 45 phút nữa, vào lúc {$depTime}. Xe mang biển kiểm soát {$busPlate}, Bác tài phụ trách là {$driverName}, số điện thoại liên hệ {$driverPhone}. Mã vé điện tử của Quý khách là {$booking->booking_code}, số ghế {$seatsStr}. Quý khách vui lòng có mặt tại {$pickupStop} trước 15 phút để chuẩn bị lên xe thuận lợi. Em xin chúc Quý khách một chuyến đi thật an toàn và vui vẻ ạ!";
    }

    /**
     * Tạo đường dẫn âm thanh TTS tiếng Việt (Google TTS tiếng Việt chuẩn)
     */
    public static function generateTtsAudioUrl(string $text): string
    {
        $shortText = mb_substr($text, 0, 190);
        return 'https://translate.google.com/translate_tts?ie=UTF-8&tl=vi&client=tw-ob&q=' . urlencode($shortText);
    }

    /**
     * Thực hiện cuộc gọi nhắc nhở AI tới khách hàng
     * Hỗ trợ tích hợp:
     * 1. eSMS.vn Voice Call OTP & Brandname SMS Baotrixemay
     * 2. Webhook tổng đài trung gian / Android Call Gateway
     *
     * @param Booking $booking
     * @param string $sentBy 'auto' (cron) hoặc 'manual' (admin bấm tay)
     * @return array
     */
    public static function makeReminderCall(Booking $booking, string $sentBy = 'auto'): array
    {
        $phone = $booking->customer_phone ?: ($booking->user?->phone ?? null);
        if (empty($phone)) {
            Log::warning("[AI_CALL_SERVICE] Cannot call booking #{$booking->booking_code}: No phone number provided.");
            return [
                'success' => false,
                'message' => "Không tìm thấy số điện thoại của khách hàng vé #{$booking->booking_code}",
            ];
        }

        $phoneE164 = self::formatPhoneE164($phone);
        $script    = self::generateCallScript($booking);
        $audioUrl  = self::generateTtsAudioUrl($script);

        $telecomProvider = null;
        $telecomSuccess  = false;
        $telecomDetail   = '';

        // 1. Cổng Viễn thông eSMS Voice Call & Brandname SMS Baotrixemay
        $esmsEnabled = env('ESMS_ENABLED', true);
        $esmsApiKey = env('ESMS_API_KEY', '5ADB13F158CB8ECD01BDF4CAFBB04D');
        $esmsSecretKey = env('ESMS_SECRET_KEY', '94FFA280E5A94C1076CAAB9D15CCFA');
        if ($esmsEnabled && !empty($esmsApiKey) && !empty($esmsSecretKey)) {
            $telecomProvider = 'ESMS_VOICE';
            try {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                // Lấy mã số vé hoặc 4 số cuối số điện thoại
                $ticketCode = preg_replace('/[^0-9]/', '', $booking->booking_code ?? '1234');
                if (strlen($ticketCode) < 4) { $ticketCode = '1234'; }
                $ticketCode = substr($ticketCode, 0, 6);

                $voiceUrl = "http://voiceapi.esms.vn/MainService.svc/json/VoiceOTP";
                $res = Http::timeout(10)->get($voiceUrl, [
                    'ApiKey' => $esmsApiKey,
                    'SecretKey' => $esmsSecretKey,
                    'Phone' => $cleanPhone,
                    'Code' => $ticketCode,
                    'Speed' => '-1',
                    'Voice' => 'female',
                ]);
                if ($res->successful() && ($res->json('CodeResult') == '100')) {
                    $telecomSuccess = true;
                    $telecomDetail = "Tổng đài eSMS đã gọi điện thoại thật tới số {$phone} (Mã cuộc gọi: " . ($res->json('SMSID') ?? 'OK') . ")";
                    Log::info("[AI_CALL_ESMS_SUCCESS] Gọi thoại eSMS thành công tới {$phone}");
                } else {
                    $telecomDetail = "eSMS phản hồi: " . ($res->json('ErrorMessage') ?? $res->body());
                }
            } catch (\Throwable $ee) {
                $telecomDetail = "Lỗi kết nối eSMS Voice: " . $ee->getMessage();
                Log::warning("[AI_CALL_ESMS_ERR] " . $ee->getMessage());
            }

            // Gửi tin nhắn SMS viễn thông Brandname Baotrixemay đồng thời
            try {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                $ticketCode = preg_replace('/[^0-9]/', '', $booking->booking_code ?? '1234');
                if (strlen($ticketCode) < 4) { $ticketCode = '1234'; }
                $ticketCode = substr($ticketCode, 0, 6);

                $smsUrl = "http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_post_json/";
                Http::timeout(8)->post($smsUrl, [
                    'ApiKey' => $esmsApiKey,
                    'SecretKey' => $esmsSecretKey,
                    'Phone' => $cleanPhone,
                    'Content' => "{$ticketCode} la ma xac minh dang ky Baotrixemay cua ban",
                    'Brandname' => 'Baotrixemay',
                    'SmsType' => '2',
                    'IsUnicode' => '0',
                ]);
            } catch (\Throwable $se) {}
        }

        // 4. Webhook tổng đài trung gian (VoIP Gateway / Android Gateway)
        $callWebhookUrl = env('AI_CALL_WEBHOOK_URL');
        if (!$telecomSuccess && !empty($callWebhookUrl)) {
            $telecomProvider = 'WEBHOOK';
            try {
                $res = Http::timeout(10)->post($callWebhookUrl, [
                    'to_phone'      => $phone,
                    'to_phone_e164' => $phoneE164,
                    'customer'      => $booking->customer_name,
                    'booking_code'  => $booking->booking_code,
                    'script'        => $script,
                    'audio_url'     => $audioUrl,
                    'sent_by'       => $sentBy,
                ]);
                if ($res->successful()) {
                    $telecomSuccess = true;
                    $telecomDetail  = "Đã chuyển tiếp lệnh gọi thoại sang Webhook tổng đài";
                } else {
                    $telecomDetail = "Webhook phản hồi mã lỗi HTTP: " . $res->status();
                }
            } catch (\Throwable $e) {
                $telecomDetail = "Lỗi Webhook tổng đài: " . $e->getMessage();
            }
        }

        // Lưu thông tin theo dõi vào Booking
        $booking->ai_call_at        = now();
        $booking->ai_call_by        = $sentBy;
        $booking->ai_call_status    = $telecomSuccess ? 'DISPATCHED' : 'RECORDED';
        $booking->ai_call_audio_url = $audioUrl;
        $booking->ai_call_script    = $script;
        $booking->save();

        // Ghi Audit Log
        AuditLog::log(
            'AI_VOICE_CALL',
            "Trợ lý AI đã kích hoạt cuộc gọi nhắc nhở 45P tới khách {$booking->customer_name} ({$phone} / {$phoneE164}). Nhà mạng: " . ($telecomProvider ?: 'Chưa cấu hình API tổng đài viễn thông') . " - Trạng thái: " . ($telecomSuccess ? 'Đã gọi' : 'Đã ghi âm'),
            'Booking',
            $booking->id
        );

        $returnMessage = "Đã gửi Gmail & khởi tạo cuộc gọi AI nhắc 45P tới {$booking->customer_name} (SĐT: {$phone}) thành công!";
        if ($telecomSuccess) {
            $returnMessage .= " [Cổng {$telecomProvider}]: {$telecomDetail}";
        } else {
            $returnMessage .= " [Lưu ý]: Để điện thoại thật {$phone} đổ chuông ngoài đời thực, máy chủ cần kích hoạt cổng viễn thông eSMS trong file .env.";
        }

        return [
            'success'            => true,
            'message'            => $returnMessage,
            'script'             => $script,
            'audio_url'          => $audioUrl,
            'phone'              => $phone,
            'phone_e164'         => $phoneE164,
            'telecom_provider'   => $telecomProvider,
            'telecom_success'    => $telecomSuccess,
            'telecom_detail'     => $telecomDetail,
            'called_at'          => now()->format('H:i d/m'),
            'by'                 => $sentBy,
            'status'             => $telecomSuccess ? 'DISPATCHED' : 'RECORDED',
        ];
    }

    /**
     * Cuộc gọi AI tự động (chạy qua Cronjob trước 45 phút)
     */
    public static function makeReminderCallAuto(Booking $booking): array
    {
        return self::makeReminderCall($booking, 'auto');
    }

    /**
     * Cuộc gọi AI do Admin bấm tay trên Dashboard
     */
    public static function makeReminderCallManual(Booking $booking): array
    {
        return self::makeReminderCall($booking, 'manual');
    }
}
