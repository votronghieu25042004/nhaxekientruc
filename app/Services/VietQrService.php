<?php

namespace App\Services;

use App\Models\Booking;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class VietQrService
{
    public const DEFAULT_BANK_ID = 'MB'; // MBBank (Ngân Hàng Quân Đội)
    public const DEFAULT_BANK_BIN = '970422'; // BIN MBBank
    public const DEFAULT_BANK_NAME = 'MBBank (Ngân Hàng Quân Đội)';
    public const DEFAULT_ACCOUNT_NO = '09025042004';
    public const DEFAULT_ACCOUNT_NAME = 'VO TRONG HIEU';

    public const MOMO_BIN = '971025'; // BIN MoMo Napas VietQR
    public const MOMO_WALLET_NO = '09025042004';
    public const MOMO_NAME = 'VO TRONG HIEU';

    /**
     * Lấy thông tin cấu hình tài khoản ngân hàng
     */
    public static function getBankConfig(): array
    {
        return [
            'bank_id'      => env('VIETQR_BANK_ID', self::DEFAULT_BANK_ID),
            'bank_bin'     => env('VIETQR_BANK_BIN', self::DEFAULT_BANK_BIN),
            'bank_name'    => env('VIETQR_BANK_NAME', self::DEFAULT_BANK_NAME),
            'account_no'   => env('VIETQR_ACCOUNT_NO', self::DEFAULT_ACCOUNT_NO),
            'account_name' => env('VIETQR_ACCOUNT_NAME', self::DEFAULT_ACCOUNT_NAME),
        ];
    }

    /**
     * Helper đóng gói Tag-Length-Value theo chuẩn EMVCo
     */
    protected static function tlv(string $tag, string $value): string
    {
        return sprintf('%02s%02d%s', $tag, strlen($value), $value);
    }

    /**
     * Tính mã CRC16-CCITT cho chuỗi EMVCo
     */
    protected static function crc16Ccitt(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return strtoupper(sprintf('%04x', $crc));
    }

    /**
     * Xây dựng chuỗi dữ liệu EMVCo VietQR động có sẵn số tiền và nội dung chuyển tiền
     */
    public static function buildEmvCoPayload(string $bin, string $accNo, int $amount, string $content): string
    {
        $guid = self::tlv('00', 'A000000727');
        $beneficiary = self::tlv('00', $bin) . self::tlv('01', $accNo);
        $consumer = $guid . self::tlv('01', $beneficiary) . self::tlv('02', 'QRIBFTTA');

        $tag38 = self::tlv('38', $consumer);
        $tag62 = self::tlv('62', self::tlv('08', $content));

        $raw = self::tlv('00', '01')
             . self::tlv('01', '12') // 12 = Dynamic QR (đã nhúng sẵn số tiền + ND chuyển khoản)
             . $tag38
             . self::tlv('53', '704') // VND
             . self::tlv('54', (string)$amount)
             . self::tlv('58', 'VN')
             . $tag62
             . '6304';

        return $raw . self::crc16Ccitt($raw);
    }

    /**
     * Sinh thông tin & URL mã VietQR Napas 247 động chuẩn cho đơn đặt vé
     */
    public static function generatePaymentData(Booking $booking, ?float $customAmount = null): array
    {
        $config = self::getBankConfig();

        // 1. Số tiền cần thanh toán (Hỗ trợ tự động nhận diện đơn cọc 30% hoặc thanh toán đủ 100%)
        $amount = $customAmount;
        if ($amount === null) {
            $isDeposit = ($booking->payment_method === 'DEPOSIT_30') 
                || in_array($booking->payment_status, ['DEPOSIT_PAID', 'PARTIALLY_PAID'])
                || str_contains($booking->notes ?? '', 'Cọc trước 30%');

            if ($isDeposit && in_array($booking->payment_status, ['UNPAID', 'PENDING', null])) {
                // Khách đang thanh toán tiền cọc 30%
                $amount = round((float)$booking->total_amount * 0.3);
            } elseif ($booking->payment_status === 'PARTIAL' || $booking->payment_status === 'DEPOSIT_PAID') {
                // Đã cọc rồi, thanh toán phần còn lại 70%
                $amount = max(0, (float)$booking->total_amount - (float)$booking->paid_amount);
            } else {
                $amount = (float)($booking->paid_amount ?: $booking->total_amount);
            }
        }
        $amount = max(1000, (int)round($amount));

        // 2. Nội dung chuyển tiền: VIẾT SÁT NHAU, KHÔNG DẤU CÁCH, KHÔNG GẠCH NGANG (e.g. VEKT2QFWP1)
        $cleanBookingCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $booking->booking_code));
        $content = 'VE' . $cleanBookingCode;

        $accNo = $config['account_no'];
        $accName = $config['account_name'];
        $accNameEncoded = urlencode($accName);
        $contentEncoded = urlencode($content);

        // 3. Chuỗi EMVCo VietQR chuẩn cho MBBank và MoMo
        $mbbankPayload = self::buildEmvCoPayload(self::DEFAULT_BANK_BIN, $accNo, $amount, $content);
        $momoPayload = self::buildEmvCoPayload(self::MOMO_BIN, self::MOMO_WALLET_NO, $amount, $content);

        // 4. Sinh mã QR cục bộ (SVG Data URI) bằng chillerlan/php-qrcode (chạy offline 0ms, không phụ thuộc mạng)
        $qrRenderer = new QRCode();
        $mbbankLocalQr = $qrRenderer->render($mbbankPayload);
        $momoLocalQr = $qrRenderer->render($momoPayload);

        // 5. URL ảnh VietQR chuẩn Napas 247 từ img.vietqr.io (có logo ngân hàng + VietQR + Napas 247 + số tiền + nội dung)
        $mbbankQrUrl = "https://img.vietqr.io/image/" . self::DEFAULT_BANK_BIN . "-{$accNo}-compact2.png?amount={$amount}&addInfo={$contentEncoded}&accountName={$accNameEncoded}";
        $momoQrUrl = "https://img.vietqr.io/image/" . self::MOMO_BIN . "-" . self::MOMO_WALLET_NO . "-compact2.png?amount={$amount}&addInfo={$contentEncoded}&accountName={$accNameEncoded}";

        return [
            // Cấu hình chung
            'bank_id'          => $config['bank_id'],
            'bank_bin'         => self::DEFAULT_BANK_BIN,
            'bank_name'        => $config['bank_name'],
            'account_no'       => $accNo,
            'account_name'     => $accName,
            'amount'           => $amount,
            'amount_formatted' => number_format($amount, 0, ',', '.') . ' đ',
            'content'          => $content, // VEKT2QFWP1 (SÁT NHAU)
            'booking_code'     => $booking->booking_code,
            'clean_code'       => $cleanBookingCode,

            // MBBank VietQR
            'mbbank_qr_url'    => $mbbankQrUrl,
            'mbbank_local_qr'  => $mbbankLocalQr,
            'mbbank_payload'   => $mbbankPayload,

            // MoMo QR
            'momo_wallet_no'   => self::MOMO_WALLET_NO,
            'momo_name'        => self::MOMO_NAME,
            'momo_qr_url'      => $momoQrUrl,
            'momo_local_qr'    => $momoLocalQr,
            'momo_payload'     => $momoPayload,
        ];
    }
}
