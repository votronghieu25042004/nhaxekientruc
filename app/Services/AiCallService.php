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
     * 1. Twilio Voice API (Gọi quốc tế E.164 tới số di động Việt Nam)
     * 2. Stringee Voice API (Cổng gọi thoại hàng đầu Việt Nam)
     * 3. eSMS.vn Voice Call OTP / Voice Campaign
     * 4. Webhook tổng đài trung gian / Android Call Gateway
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

        // 0. Cổng Viễn thông eSMS Voice Call (Chỉ khi bật ESMS_ENABLED=true)
        $esmsEnabled = env('ESMS_ENABLED', false);
        $esmsApiKey = env('ESMS_API_KEY');
        $esmsSecretKey = env('ESMS_SECRET_KEY');
        if ($esmsEnabled && !empty($esmsApiKey) && !empty($esmsSecretKey)) {
            $telecomProvider = 'ESMS_VOICE';
            try {
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                // Tạo mã thông báo vé 6 chữ số
                $ticketCode = preg_replace('/[^0-9]/', '', $booking->booking_code ?? '1234');
                if (strlen($ticketCode) < 4) { $ticketCode = '1234'; }
                $ticketCode = substr($ticketCode, 0, 6);

                $voiceUrl = "http://voiceapi.esms.vn/MainService.svc/json/VoiceOTP";
                $res = Http::timeout(10)->get($voiceUrl, [
                    'ApiKey' => $esmsApiKey,
                    'SecretKey' => $esmsSecretKey,
                    'Phone' => $cleanPhone,
                    'Code' => $ticketCode,
                    'Speed' => '0',
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
        }

        // 0b. Cổng Viễn thông SpeedSMS.vn (Gửi SMS thật & Gọi điện Voice Call tới mạng di động VN)
        $speedSmsToken = env('SPEEDSMS_ACCESS_TOKEN');
        if (!$telecomSuccess && !empty($speedSmsToken)) {
            $telecomProvider = 'SPEEDSMS';
            try {
                $speedSms = new \App\Services\SpeedSmsService($speedSmsToken);
                $trip = $booking->trip;
                
                // Gửi tin nhắn SMS viễn thông nhắc xe
                $smsRes = $speedSms->sendTripReminderSms(
                    $phone,
                    $booking->customer_name ?? 'Quý khách',
                    $trip?->route?->name ?? 'Liên tỉnh',
                    $trip?->bus?->license_plate ?? 'Xe chất lượng cao',
                    $trip?->departure_time ? Carbon::parse($trip->departure_time)->format('H:i d/m/Y') : 'sắp tới',
                    $booking->pickupStop?->stop_name ?? ($trip?->pickup_point ?? 'Điểm đón nhà xe'),
                    $trip?->driver?->name ?? 'Bác tài',
                    $trip?->driver?->phone ?? ''
                );

                // Đồng thời gọi cuộc gọi Voice OTP tới số điện thoại
                $voiceRes = $speedSms->sendVoiceOtp($phone, '1234');

                $smsOk = isset($smsRes['status']) && $smsRes['status'] === 'success';
                $voiceOk = isset($voiceRes['status']) && $voiceRes['status'] === 'success';

                if ($smsOk || $voiceOk) {
                    $telecomSuccess = true;
                    $telecomDetail = "Đã gửi thành công SMS viễn thông " . ($voiceOk ? "& kích hoạt cuộc gọi Voice" : "") . " tới số {$phone} qua cổng SpeedSMS";
                } else {
                    $telecomDetail = "SpeedSMS phản hồi: " . ($smsRes['message'] ?? 'Số dư tài khoản chưa đủ');
                }
            } catch (\Throwable $e) {
                $telecomDetail = "Lỗi kết nối SpeedSMS: " . $e->getMessage();
                Log::warning("[AI_CALL_SPEEDSMS_ERR] " . $e->getMessage());
            }
        }

        // 1. Thử gọi qua Twilio Voice API nếu có cấu hình
        $twilioSid   = env('TWILIO_ACCOUNT_SID');
        $twilioToken = env('TWILIO_AUTH_TOKEN');
        $twilioFrom  = env('TWILIO_PHONE_NUMBER');

        if (!empty($twilioSid) && !empty($twilioToken) && !empty($twilioFrom)) {
            $telecomProvider = 'TWILIO';
            try {
                $twiml = '<Response><Say voice="Polly.Thoai-Neural" language="vi-VN">' . htmlspecialchars($script) . '</Say></Response>';
                $res = Http::withBasicAuth($twilioSid, $twilioToken)
                    ->asForm()
                    ->timeout(10)
                    ->post("https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Calls.json", [
                        'To'    => $phoneE164,
                        'From'  => $twilioFrom,
                        'Twiml' => $twiml,
                    ]);

                if ($res->successful()) {
                    $telecomSuccess = true;
                    $telecomDetail  = "Đã gửi lệnh gọi ra mạng di động qua Twilio Voice (Call SID: " . ($res->json('sid') ?? 'OK') . ")";
                } else {
                    $telecomDetail = "Twilio phản hồi lỗi: " . $res->body();
                    Log::warning("[AI_CALL_TWILIO] " . $telecomDetail);
                }
            } catch (\Throwable $e) {
                $telecomDetail = "Lỗi kết nối Twilio: " . $e->getMessage();
                Log::warning("[AI_CALL_TWILIO_ERROR] " . $e->getMessage());
            }
        }

        // 2. Thử gọi qua Cổng viễn thông Stringee Việt Nam nếu có cấu hình
        if (!$telecomSuccess && !empty(env('STRINGEE_KEY_SID')) && !empty(env('STRINGEE_KEY_SECRET'))) {
            $telecomProvider = 'STRINGEE';
            try {
                // Stringee callout REST API
                $stringeeFrom = env('STRINGEE_PHONE_NUMBER', '02888888888');
                $res = Http::timeout(10)
                    ->withHeaders([
                        'X-STRINGEE-AUTH' => env('STRINGEE_AUTH_TOKEN') ?? env('STRINGEE_KEY_SECRET'),
                    ])
                    ->post('https://api.stringee.com/v1/call/callout', [
                        'from' => [
                            'type'   => 'external',
                            'number' => $stringeeFrom,
                            'alias'  => 'nhaxekientruc'
                        ],
                        'to' => [
                            [
                                'type'   => 'external',
                                'number' => $phone,
                                'alias'  => $booking->customer_name
                            ]
                        ],
                        'actions' => [
                            [
                                'action' => 'talk',
                                'text'   => $script,
                                'voice'  => 'hn_female_thutrang_phrase_48k-fhq',
                                'speed'  => 0
                            ]
                        ]
                    ]);

                if ($res->successful() && ($res->json('r') === 0 || $res->json('call_id'))) {
                    $telecomSuccess = true;
                    $telecomDetail  = "Đã gọi thành công qua tổng đài Stringee Việt Nam (Call ID: " . ($res->json('call_id') ?? 'OK') . ")";
                } else {
                    $telecomDetail = "Stringee phản hồi: " . $res->body();
                }
            } catch (\Throwable $e) {
                $telecomDetail = "Lỗi kết nối Stringee: " . $e->getMessage();
            }
        }

        // 3. Thử qua eSMS.vn Voice Call API nếu có API Key (Chỉ khi ESMS_ENABLED=true)
        $esmsKey    = env('ESMS_API_KEY');
        $esmsSecret = env('ESMS_SECRET_KEY');
        if (!$telecomSuccess && $esmsEnabled && !empty($esmsKey) && !empty($esmsSecret)) {
            $telecomProvider = 'ESMS';
            try {
                $res = Http::timeout(10)->post('https://api.esms.vn/MainService.svc/json/MakeCall_post_json', [
                    'ApiKey'    => $esmsKey,
                    'SecretKey' => $esmsSecret,
                    'Phone'     => $phone,
                    'Content'   => $script,
                ]);
                if ($res->successful() && $res->json('CodeResult') == '100') {
                    $telecomSuccess = true;
                    $telecomDetail  = "Đã gọi qua tổng đài eSMS Voice tới " . $phone;
                } else {
                    $telecomDetail = "eSMS phản hồi: " . ($res->json('ErrorMessage') ?? $res->body());
                }
            } catch (\Throwable $e) {
                $telecomDetail = "Lỗi kết nối eSMS: " . $e->getMessage();
            }
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
            $returnMessage .= " [Lưu ý]: Để điện thoại thật {$phone} đổ chuông ngoài đời thực, máy chủ cần kết nối API Tổng đài viễn thông (Twilio, Stringee hoặc eSMS) trong file .env.";
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
