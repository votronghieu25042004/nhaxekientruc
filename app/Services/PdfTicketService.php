<?php

namespace App\Services;

use App\Models\Booking;

/**
 * PdfTicketService - Sinh HTML vé điện tử đẹp đính kèm email
 */
class PdfTicketService
{
    public static function generateTicketHtml(Booking $booking): array
    {
        $booking->loadMissing(['trip.route', 'trip.bus', 'trip.driver', 'pickupStop', 'dropoffStop', 'bookingSeats']);

        $trip        = $booking->trip;
        $seatsArr    = $booking->bookingSeats->pluck('seat_number')->filter()->values()->toArray();
        $seatsStr    = implode(', ', $seatsArr) ?: 'Chỗ đặt trực tuyến';

        $routeName   = $trip?->route?->name ?? 'Tuyến xe nhaxekientruc';
        $origin      = $trip?->route?->origin ?? 'Điểm xuất phát';
        $destination = $trip?->route?->destination ?? 'Điểm đến';
        $depTime     = $trip?->departure_time
            ? \Carbon\Carbon::parse($trip->departure_time)->format('H:i - d/m/Y')
            : 'Xem trên vé';
        $pickupStop  = $booking->pickupStop?->stop_name ?? $origin;
        $dropoffStop = $booking->dropoffStop?->stop_name ?? $destination;
        $busPlate    = $trip?->bus?->license_plate ?? '43B-000.00';
        $busType     = $trip?->bus?->bus_type ?? 'Xe Giường Nằm VIP';
        $driverName  = $trip?->driver?->name ?? 'Bác tài nhaxekientruc';

        $totalAmount = number_format((float)($booking->total_amount ?? 0)) . ' &#8363;';
        $paidAmount  = number_format((float)($booking->paid_amount ?? 0)) . ' &#8363;';
        $remaining   = number_format(max(0, (float)($booking->total_amount ?? 0) - (float)($booking->paid_amount ?? 0))) . ' &#8363;';

        $bookingCode   = $booking->booking_code;
        $customerName  = htmlspecialchars($booking->customer_name ?? 'Quý Khách');
        $customerPhone = htmlspecialchars($booking->customer_phone ?? '');
        $customerEmail = htmlspecialchars($booking->customer_email ?? '');

        $payLabel = match ($booking->payment_status) {
            'PAID'      => 'ĐÃ THANH TOÁN',
            'PARTIAL'   => 'CỌC 1 PHẦN',
            'UNPAID'    => 'CHƯA THANH TOÁN',
            'CANCELLED' => 'ĐÃ HUỶ',
            default     => $booking->payment_status ?? 'PENDING',
        };
        $payColor = match ($booking->payment_status) {
            'PAID'      => '#16a34a',
            'PARTIAL'   => '#d97706',
            'CANCELLED' => '#6b7280',
            default     => '#dc2626',
        };

        $qrData   = urlencode(url('/ve/' . $bookingCode));
        $qrImgUrl = "https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={$qrData}&ecc=H";
        $printAt  = now()->format('H:i - d/m/Y');

        $html = '<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8">
<title>Ve Dien Tu - ' . $bookingCode . '</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f1f5f9;padding:20px;display:flex;justify-content:center}
.wrap{max-width:680px;width:100%}
.ticket{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.12)}
.hdr{background:linear-gradient(135deg,#1e3a8a,#3b82f6,#06b6d4);padding:24px 28px;display:flex;align-items:center;justify-content:space-between}
.hdr-brand h1{color:#fff;font-size:20px;font-weight:800}
.hdr-brand p{color:rgba(255,255,255,.75);font-size:12px;margin-top:2px}
.ticket-badge{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);border-radius:10px;padding:8px 16px;text-align:center}
.ticket-badge .lbl{color:rgba(255,255,255,.8);font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase}
.ticket-badge .cd{color:#fff;font-size:22px;font-weight:800;font-family:monospace}
.route-strip{background:linear-gradient(90deg,#0f172a,#1e3a8a);padding:18px 28px;display:flex;align-items:center}
.city{color:#fff;min-width:160px}
.city .nm{font-size:20px;font-weight:800}
.city .st{font-size:11px;color:rgba(255,255,255,.6);margin-top:3px}
.arrow-wrap{flex:1;display:flex;flex-direction:column;align-items:center;padding:0 16px}
.arrow-line{width:100%;height:2px;background:linear-gradient(90deg,#60a5fa,#34d399);border-radius:1px;position:relative}
.arrow-line::after{content:"&#9658;";position:absolute;right:-6px;top:-9px;color:#34d399;font-size:14px}
.dep{color:#fbbf24;font-size:13px;font-weight:700;margin-top:8px}
.body{padding:22px 28px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}
.ii .lbl{font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;margin-bottom:3px}
.ii .val{font-size:14px;font-weight:700;color:#1e293b}
.ii .val.blue{color:#2563eb}
.ii .val.seat{font-family:monospace;font-size:15px;color:#7c3aed;background:#ede9fe;border-radius:6px;padding:2px 8px;display:inline-block}
.pay-row{background:#f8fafc;border-radius:10px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px}
.pi .plbl{font-size:10px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.pi .pval{font-size:17px;font-weight:800;color:#1e293b;margin-top:2px}
.pi .pval.grn{color:#16a34a}
.pi .pval.red{color:#dc2626}
.sbadge{padding:6px 14px;border-radius:999px;font-size:11px;font-weight:700;color:#fff}
.bottom{display:flex;gap:20px;align-items:flex-start}
.qr-box{flex-shrink:0;text-align:center}
.qr-box img{width:160px;height:160px;border-radius:10px;border:2px solid #e2e8f0}
.qr-lbl{font-size:10px;color:#64748b;margin-top:7px;font-weight:700;line-height:1.5}
.bus-info{flex:1}
.bir{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f1f5f9}
.bir:last-child{border-bottom:none}
.bi-icon{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.bl{background:#dbeafe}.bpu{background:#ede9fe}.bgr{background:#dcfce7}
.bid .bil{font-size:10px;color:#94a3b8;font-weight:700}
.bid .biv{font-size:13px;font-weight:700;color:#1e293b}
.ftr{background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 28px;display:flex;justify-content:space-between;align-items:center}
.ftr-note{font-size:10px;color:#94a3b8}
.ftr-note strong{color:#64748b}
</style></head><body>
<div class="wrap"><div class="ticket">
<div class="hdr">
  <div class="hdr-brand">
    <div>
      <h1>nhaxekientruc</h1>
      <p>Truong DH Kien Truc Da Nang &bull; Ve Xe Dien Tu</p>
    </div>
  </div>
  <div class="ticket-badge">
    <div class="lbl">Ma Ve</div>
    <div class="cd">' . $bookingCode . '</div>
  </div>
</div>
<div class="route-strip">
  <div class="city">
    <div class="nm">' . htmlspecialchars($origin) . '</div>
    <div class="st">&#128657; ' . htmlspecialchars($pickupStop) . '</div>
  </div>
  <div class="arrow-wrap">
    <div class="arrow-line"></div>
    <div class="dep">&#128336; ' . $depTime . '</div>
  </div>
  <div class="city" style="text-align:right">
    <div class="nm">' . htmlspecialchars($destination) . '</div>
    <div class="st">&#127937; ' . htmlspecialchars($dropoffStop) . '</div>
  </div>
</div>
<div class="body">
  <div class="grid">
    <div class="ii"><div class="lbl">Hanh Khach</div><div class="val">' . $customerName . '</div></div>
    <div class="ii"><div class="lbl">So Dien Thoai</div><div class="val blue">' . $customerPhone . '</div></div>
    <div class="ii"><div class="lbl">Tuyen Xe</div><div class="val">' . htmlspecialchars($routeName) . '</div></div>
    <div class="ii"><div class="lbl">So Ghe</div><div class="val"><span class="seat">' . htmlspecialchars($seatsStr) . '</span></div></div>
  </div>
  <div class="pay-row">
    <div class="pi"><div class="plbl">Tong Tien</div><div class="pval">' . $totalAmount . '</div></div>
    <div class="pi"><div class="plbl">Da Thanh Toan</div><div class="pval grn">' . $paidAmount . '</div></div>
    <div class="pi"><div class="plbl">Con Lai</div><div class="pval red">' . $remaining . '</div></div>
    <span class="sbadge" style="background:' . $payColor . '">' . $payLabel . '</span>
  </div>
  <div class="bottom">
    <div class="qr-box">
      <img src="' . $qrImgUrl . '" alt="QR">
      <div class="qr-lbl">&#128247; Quet QR Check-in<br>tai nha xe</div>
    </div>
    <div class="bus-info">
      <div class="bir"><div class="bi-icon bl">&#128652;</div><div class="bid"><div class="bil">Xe / Bien So</div><div class="biv">' . htmlspecialchars($busPlate) . ' &bull; ' . htmlspecialchars($busType) . '</div></div></div>
      <div class="bir"><div class="bi-icon bpu">&#128104;&#8205;&#9992;</div><div class="bid"><div class="bil">Bac Tai Xe</div><div class="biv">' . htmlspecialchars($driverName) . '</div></div></div>
      <div class="bir"><div class="bi-icon bgr">&#128231;</div><div class="bid"><div class="bil">Email</div><div class="biv">' . $customerEmail . '</div></div></div>
    </div>
  </div>
</div>
<div class="ftr">
  <div class="ftr-note"><strong>Luu y:</strong> Xuat trinh ve khi len xe &bull; Giu dien thoai san de quet QR</div>
  <div class="ftr-note">In luc ' . $printAt . '</div>
</div>
</div></div></body></html>';

        return [
            'html'     => $html,
            'filename' => 've-' . strtolower($bookingCode) . '.html',
        ];
    }

    /**
     * Sinh HTML Biên Nhận Hủy Vé & Hoàn Tiền đính kèm email Admin
     */
    public static function generateRefundReceiptHtml(Booking $booking, array $refundData = []): array
    {
        $booking->loadMissing(['trip.route']);
        $trip = $booking->trip;
        $bookingCode = $booking->booking_code;
        $customerName = htmlspecialchars($booking->customer_name ?: 'Quý Khách');
        $customerPhone = htmlspecialchars($booking->customer_phone ?: '');
        $routeName = htmlspecialchars($trip?->route?->name ?? 'Tuyến xe nhaxekientruc');
        $refundAmount = number_format((float)($refundData['refundAmount'] ?? $booking->refund_amount ?? 0), 0, ',', '.') . ' VNĐ';
        $bankName = htmlspecialchars($refundData['bankName'] ?? ($booking->refund_bank_name ?: 'Chuyển khoản Ngân hàng'));
        $accountNumber = htmlspecialchars($refundData['accountNumber'] ?? ($booking->refund_account_number ?: 'Tài khoản gốc'));
        $accountName = htmlspecialchars($refundData['accountName'] ?? ($booking->refund_account_name ?: $booking->customer_name));
        $txCode = htmlspecialchars($refundData['txCode'] ?? ('REF-' . time()));
        $adminNotes = htmlspecialchars($refundData['adminNotes'] ?? ($booking->refund_admin_notes ?: 'Đã hoàn tiền theo quy chế'));
        $processedAt = now()->format('H:i - d/m/Y');

        $html = '<!DOCTYPE html><html lang="vi"><head>
<meta charset="UTF-8">
<title>Bien Nhan Hoan Tien - ' . $bookingCode . '</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f8fafc;padding:24px;display:flex;justify-content:center}
.wrap{max-width:640px;width:100%;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08);border:1px solid #e2e8f0}
.hdr{background:linear-gradient(135deg,#b91c1c,#dc2626,#ea580c);padding:24px 28px;color:#fff}
.hdr h1{font-size:18px;font-weight:900;text-transform:uppercase;letter-spacing:0.5px}
.hdr p{font-size:12px;opacity:0.85;margin-top:3px}
.badge-code{display:inline-block;background:rgba(255,255,255,0.2);padding:4px 12px;border-radius:8px;font-family:monospace;font-weight:900;font-size:15px;margin-top:10px}
.body{padding:24px 28px}
.status-pill{background:#ecfdf5;border:1.5px solid #a7f3d0;color:#065f46;padding:12px 16px;border-radius:12px;font-size:13px;font-weight:800;display:flex;align-items:center;gap:8px;margin-bottom:20px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px}
.item .lbl{font-size:10.5px;color:#64748b;font-weight:700;text-transform:uppercase;margin-bottom:3px}
.item .val{font-size:13.5px;color:#0f172a;font-weight:800}
.item .val.red{color:#dc2626}
.item .val.grn{color:#059669;font-size:16px;font-family:monospace}
.box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;margin-bottom:16px}
.box-title{font-size:11px;font-weight:800;color:#334155;text-transform:uppercase;margin-bottom:8px}
.ftr{background:#f1f5f9;border-top:1px solid #e2e8f0;padding:16px 28px;font-size:11px;color:#64748b;display:flex;justify-content:space-between;align-items:center}
</style></head><body>
<div class="wrap">
  <div class="hdr">
    <h1>TRƯỜNG ĐH KIẾN TRÚC ĐÀ NẴNG - NHAXEKIENTRUC</h1>
    <p>Biên Nhận Hủy Vé & Xác Nhận Chuyển Khoản Hoàn Tiền (Lưu Trữ Quản Trị)</p>
    <div class="badge-code">MÃ VÉ: #' . $bookingCode . ' &bull; GD: #' . $txCode . '</div>
  </div>
  <div class="body">
    <div class="status-pill">
      &#10004; ĐÃ PHÊ DUYỆT HOÀN TIỀN THÀNH CÔNG VÀO TÀI KHOẢN KHÁCH HÀNG
    </div>
    <div class="grid">
      <div class="item"><div class="lbl">Hành khách:</div><div class="val">' . $customerName . '</div></div>
      <div class="item"><div class="lbl">Số điện thoại:</div><div class="val">' . $customerPhone . '</div></div>
      <div class="item"><div class="lbl">Tuyến đường:</div><div class="val">' . $routeName . '</div></div>
      <div class="item"><div class="lbl">Thời gian xử lý:</div><div class="val">' . $processedAt . '</div></div>
      <div class="item"><div class="lbl">Số tiền hoàn trả:</div><div class="val grn">' . $refundAmount . '</div></div>
      <div class="item"><div class="lbl">Mã giao dịch ngân hàng:</div><div class="val red">' . $txCode . '</div></div>
    </div>
    <div class="box">
      <div class="box-title">&#127974; THÔNG TIN TÀI KHOẢN NHẬN TIỀN CỦA KHÁCH:</div>
      <div style="font-size:12.5px;line-height:1.7;color:#1e293b">
        &bull; Ngân hàng: <strong>' . $bankName . '</strong><br>
        &bull; Số tài khoản: <strong style="font-family:monospace;font-size:13.5px;color:#b91c1c">' . $accountNumber . '</strong><br>
        &bull; Tên chủ tài khoản: <strong>' . $accountName . '</strong><br>
        &bull; Ghi chú phê duyệt: <em>' . $adminNotes . '</em>
      </div>
    </div>
  </div>
  <div class="ftr">
    <div>Hệ thống Quản Trị nhaxekientruc Terminal</div>
    <div>Lưu trữ lúc: ' . $processedAt . '</div>
  </div>
</div>
</body></html>';

        return [
            'html'     => $html,
            'filename' => 'bien-nhan-hoan-tien-' . strtolower($bookingCode) . '.html',
        ];
    }
}
