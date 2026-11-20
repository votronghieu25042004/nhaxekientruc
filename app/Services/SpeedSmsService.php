<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpeedSmsService
{
    protected string $accessToken;
    protected string $rootUrl = 'https://api.speedsms.vn/index.php';

    public function __construct(?string $accessToken = null)
    {
        $this->accessToken = $accessToken ?? (string) env('SPEEDSMS_ACCESS_TOKEN', '');
    }

    /**
     * Kiểm tra thông tin tài khoản và số dư SpeedSMS
     */
    public function getUserInfo(): array
    {
        if (empty($this->accessToken)) {
            return ['status' => 'error', 'message' => 'SPEEDSMS_ACCESS_TOKEN chưa được cấu hình'];
        }

        try {
            $response = Http::withBasicAuth($this->accessToken, 'x')
                ->timeout(10)
                ->get("{$this->rootUrl}/user/info");

            return $response->json() ?? ['status' => 'error', 'message' => 'Phản hồi không hợp lệ'];
        } catch (\Throwable $e) {
            Log::error("[SPEEDSMS_INFO_ERR] " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Chuẩn hóa số điện thoại theo định dạng SpeedSMS: 849xxxxxxxx
     */
    public function formatPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '84' . substr($clean, 1);
        } elseif (!str_starts_with($clean, '84')) {
            $clean = '84' . $clean;
        }
        return $clean;
    }

    /**
     * Gửi tin nhắn SMS thật qua SpeedSMS
     * @param string|array $phones Số điện thoại hoặc mảng các số điện thoại
     * @param string $content Nội dung tin nhắn
     * @param int $smsType 2: Đầu số ngẫu nhiên, 3: Brandname, 4: Brandname mặc định
     */
    public function sendSms(string|array $phones, string $content, int $smsType = 2, string $sender = ''): array
    {
        if (empty($this->accessToken)) {
            return ['status' => 'error', 'message' => 'SPEEDSMS_ACCESS_TOKEN chưa được cấu hình'];
        }

        $phoneList = is_array($phones) ? $phones : [$phones];
        $formattedPhones = array_map([$this, 'formatPhone'], $phoneList);

        try {
            $payload = [
                'to' => $formattedPhones,
                'content' => $content,
                'sms_type' => $smsType,
                'sender' => $sender,
            ];

            $response = Http::withBasicAuth($this->accessToken, 'x')
                ->timeout(12)
                ->post("{$this->rootUrl}/sms/send", $payload);

            $resData = $response->json();
            Log::info("[SPEEDSMS_SEND_SMS] To: " . implode(',', $formattedPhones) . " | Res: " . json_encode($resData, JSON_UNESCAPED_UNICODE));
            return $resData ?? ['status' => 'error', 'message' => 'Lỗi kết nối SpeedSMS'];
        } catch (\Throwable $e) {
            Log::error("[SPEEDSMS_SEND_ERR] " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Gửi tin nhắn SMS chứa mã OTP
     */
    public function sendOtpSms(string $phone, string $otp): array
    {
        $content = "Ma xac thuc nha xe Kien Truc cua ban la: {$otp}. Vui long khong chia se ma nay voi bat ky ai.";
        return $this->sendSms($phone, $content, 2);
    }

    /**
     * Thực hiện cuộc gọi Voice OTP tự động đọc mã
     */
    public function sendVoiceOtp(string $phone, string $otp): array
    {
        if (empty($this->accessToken)) {
            return ['status' => 'error', 'message' => 'SPEEDSMS_ACCESS_TOKEN chưa được cấu hình'];
        }

        $formattedPhone = $this->formatPhone($phone);

        try {
            // Thử endpoint voice/otp hoặc voice/send
            $response = Http::withBasicAuth($this->accessToken, 'x')
                ->timeout(15)
                ->post("{$this->rootUrl}/voice/otp", [
                    'to' => $formattedPhone,
                    'content' => $otp,
                ]);

            if ($response->status() === 404 || !$response->successful()) {
                $response = Http::withBasicAuth($this->accessToken, 'x')
                    ->timeout(15)
                    ->post("{$this->rootUrl}/voice/send", [
                        'to' => $formattedPhone,
                        'content' => $otp,
                    ]);
            }

            $resData = $response->json();
            Log::info("[SPEEDSMS_VOICE_OTP] To: {$formattedPhone} | Res: " . json_encode($resData, JSON_UNESCAPED_UNICODE));
            return $resData ?? ['status' => 'error', 'message' => 'Lỗi kết nối Voice SpeedSMS'];
        } catch (\Throwable $e) {
            Log::error("[SPEEDSMS_VOICE_ERR] " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Gửi SMS nhắc nhở chuyến xe trước khi khởi hành (Do Admin hoặc Bác tài kích hoạt)
     */
    public function sendTripReminderSms(
        string $phone,
        string $customerName,
        string $route,
        string $licensePlate,
        string $departureTime,
        string $pickupStop,
        string $driverName = '',
        string $driverPhone = ''
    ): array {
        $driverInfo = !empty($driverPhone) ? " Bac tai: {$driverName} ({$driverPhone})." : "";
        $content = "Nha xe Kien Truc: Chuyen {$route} bien so {$licensePlate} khoi hanh luc {$departureTime}. Quy khach {$customerName} vui long co mat tai diem don: {$pickupStop} truoc 15 phut.{$driverInfo} Chuc quy khach thuong lo binh an!";

        return $this->sendSms($phone, $content, 2);
    }
}
