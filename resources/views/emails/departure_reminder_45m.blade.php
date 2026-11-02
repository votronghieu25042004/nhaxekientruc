<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhắc Nhở Xuất Bến Trước 45 Phút - nhaxekientruc Terminal</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f0305; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0f0305; padding: 30px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); border: 1px solid #fecdd3;">
                    
                    <!-- HEADER BANNER: OBSIDIAN CRIMSON WITH GOLD ACCENTS -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #450a0a 0%, #7f1d1d 50%, #991b1b 100%); padding: 36px 30px; text-align: center; border-bottom: 3px solid #fbbf24;">
                            <div style="display: inline-block; width: 48px; height: 48px; line-height: 48px; border-radius: 16px; background-color: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); font-size: 24px; margin-bottom: 12px;">
                                🚌
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase;">
                                NHAXEKIENTRUC TERMINAL
                            </h1>
                            <div style="display: inline-block; margin-top: 8px; background-color: rgba(251, 191, 36, 0.2); border: 1px solid #fbbf24; border-radius: 20px; padding: 4px 14px;">
                                <span style="color: #fef08a; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                                    ⏰ NHẮC NHỞ KHỞI HÀNH TRƯỚC 45 PHÚT
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- GREETING & URGENCY NOTICE -->
                    <tr>
                        <td style="padding: 32px 32px 16px 32px;">
                            <p style="margin: 0 0 14px 0; font-size: 16px; font-weight: 800; color: #0f172a;">
                                Kính gửi Quý khách: <span style="color: #dc2626;">{{ $customerName }}</span>,
                            </p>
                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Chuyến xe của quý khách từ <strong style="color: #0f172a;">{{ $routeName }}</strong> chuẩn bị xuất bến trong <strong>khoảng 45 phút tới</strong>. Vui lòng kiểm tra lại thông tin vé và có mặt tại điểm đón trước <strong>15–20 phút</strong> để lơ xe và bác tài hướng dẫn lên xe thuận lợi nhất.
                            </p>

                            <!-- HIGHLIGHT BOX: TICKET CODE & QR LINK -->
                            <div style="background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%); border: 2px dashed #fca5a5; border-radius: 18px; padding: 20px; text-align: center; margin: 20px 0;">
                                <span style="display: block; font-size: 11px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">
                                    MÃ VÉ ĐIỆN TỬ
                                </span>
                                <div style="font-family: 'Courier New', Courier, monospace; font-size: 28px; font-weight: 900; letter-spacing: 4px; color: #b91c1c;">
                                    #{{ $bookingCode }}
                                </div>
                                <div style="margin-top: 10px;">
                                    <span style="display: inline-block; background-color: #fee2e2; color: #991b1b; font-size: 12px; font-weight: 800; padding: 4px 12px; border-radius: 12px;">
                                        Vị trí ghế: <strong style="color: #7f1d1d;">{{ $seatNumbers }}</strong>
                                    </span>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- TRIP DETAILS CARD -->
                    <tr>
                        <td style="padding: 0 32px 24px 32px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 18px; padding: 22px;">
                                <h3 style="margin: 0 0 16px 0; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #334155; border-bottom: 1px solid #e2e8f0; pb: 10px; padding-bottom: 10px;">
                                    📋 Chi Tiết Lịch Trình Chuyến Xe
                                </h3>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 13px; line-height: 1.8;">
                                    <tr>
                                        <td width="38%" style="color: #64748b; font-weight: 600;">Mã chuyến xe:</td>
                                        <td width="62%" style="color: #0f172a; font-weight: 800; font-family: monospace;">{{ $tripCode }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;">Tuyến đường:</td>
                                        <td style="color: #b91c1c; font-weight: 800;">{{ $routeName }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;">Giờ khởi hành:</td>
                                        <td style="color: #047857; font-weight: 800;">{{ $departureTime }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;">Điểm đón xe:</td>
                                        <td style="color: #0f172a; font-weight: 700;">{{ $pickupStop }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;">Biển số xe:</td>
                                        <td style="color: #0f172a; font-weight: 800; font-family: monospace;">{{ $busPlate }} ({{ $busType }})</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;">Bác tài phụ trách:</td>
                                        <td style="color: #0f172a; font-weight: 700;">
                                            {{ $driverName }} • <a href="tel:{{ $driverPhone }}" style="color: #2563eb; text-decoration: none; font-weight: 800;">{{ $driverPhone }}</a>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- CALL TO ACTION: OPEN TICKET -->
                    <tr>
                        <td style="padding: 0 32px 28px 32px; text-align: center;">
                            <a href="{{ $ticketUrl }}" target="_blank" style="display: inline-block; width: 85%; padding: 14px 20px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 900; border-radius: 14px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 8px 16px rgba(220, 38, 38, 0.35);">
                                📱 Mở Vé Điện Tử & Mã QR Check-in →
                            </a>
                            <p style="margin: 12px 0 0 0; font-size: 11px; color: #64748b;">
                                Khi lên xe, quý khách chỉ cần mở điện thoại đưa mã QR cho Bác tài hoặc Lơ xe quét trong 1 giây.
                            </p>
                        </td>
                    </tr>

                    <!-- IMPORTANT TRAVEL NOTES -->
                    <tr>
                        <td style="padding: 0 32px 28px 32px;">
                            <div style="background-color: #fefce8; border-left: 4px solid #eab308; border-radius: 8px; padding: 14px 16px;">
                                <strong style="color: #854d0e; font-size: 12px; display: block; margin-bottom: 4px;">
                                    💡 LƯU Ý DÀNH CHO QUÝ KHÁCH:
                                </strong>
                                <ul style="margin: 0; padding-left: 18px; font-size: 11.5px; color: #713f12; line-height: 1.6;">
                                    <li>Lơ xe sẽ gọi điện thoại theo số đã đăng ký nếu sắp đến giờ chạy mà chưa thấy quý khách check-in.</li>
                                    <li>Vui lòng giữ điện thoại luôn mở chuông và sẵn sàng nhận cuộc gọi.</li>
                                    <li>Hành lý xách tay tiêu chuẩn dưới 20kg. Không mang theo chất dễ cháy nổ hoặc hàng cấm.</li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 6px 0; font-size: 12px; font-weight: 700; color: #475569;">
                                TỔNG ĐÀI ĐIỀU HÀNH & HỖ TRỢ KHÁCH HÀNG 24/7
                            </p>
                            <p style="margin: 0 0 12px 0; font-size: 13px; font-weight: 900; color: #dc2626;">
                                📞 Hotline: 1900 6067 • 0905 123 456
                            </p>
                            <p style="margin: 0; font-size: 10.5px; color: #94a3b8; line-height: 1.5;">
                                © 2026 nhaxekientruc Terminal Vietnam. Hệ thống vận tải công nghệ cao.<br>
                                Email này được gửi tự động từ hệ thống quản lý vé nhaxekientruc Đà Nẵng.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
