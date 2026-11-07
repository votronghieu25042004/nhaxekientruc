<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Nhắc Nhở Xuất Bến Trước 45 Phút - nhaxekientruc</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-padding { padding: 22px 16px !important; }
            .header-padding { padding: 26px 18px !important; }
            .hero-box { padding: 18px 14px !important; }
            .footer-padding { padding: 20px 16px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 32px 12px;">
        <tr>
            <td align="center">
                <!-- MAIN CARD (MAX 600PX) -->
                <table role="presentation" class="email-container" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 12px 36px rgba(0, 0, 0, 0.07), 0 2px 6px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0;">
                    
                    <!-- 1. TOP BRAND HEADER -->
                    <tr>
                        <td class="header-padding" style="background: linear-gradient(135deg, #d97706 0%, #dc2626 50%, #991b1b 100%); padding: 32px 28px; text-align: center; border-bottom: 3px solid #f59e0b;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <!-- Alarm Badge Icon -->
                                        <div style="display: inline-block; width: 50px; height: 50px; line-height: 50px; border-radius: 16px; background-color: rgba(255, 255, 255, 0.2); border: 1.5px solid rgba(255, 255, 255, 0.4); font-size: 26px; text-align: center; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                            ⏰
                                        </div>
                                        <!-- Brand Title -->
                                        <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase;">
                                            nhaxekientruc
                                        </h1>
                                        <!-- Tagline Badge -->
                                        <div style="margin-top: 6px;">
                                            <span style="display: inline-block; background-color: rgba(0, 0, 0, 0.25); color: #fef08a; font-size: 11px; font-weight: 800; padding: 4px 14px; border-radius: 20px; border: 1px solid rgba(254, 240, 138, 0.5); letter-spacing: 0.8px; text-transform: uppercase;">
                                                ⏰ NHẮC NHỞ KHỞI HÀNH TRƯỚC 45 PHÚT
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 2. ROUTE HIGHLIGHT STRIP -->
                    <tr>
                        <td style="background-color: #fff7ed; padding: 12px 24px; text-align: center; border-bottom: 1px solid #fed7aa;">
                            <span style="color: #9a3412; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">
                                🚌 Chuyến: {{ $tripCode }} • {{ $routeName }}
                            </span>
                        </td>
                    </tr>

                    <!-- 3. GREETING & URGENCY HERO -->
                    <tr>
                        <td class="content-padding" style="padding: 28px 30px 16px 30px;">
                            <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: 800; color: #0f172a;">
                                Kính gửi Quý khách: <span style="color: #dc2626;">{{ $customerName }}</span>,
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.65; color: #475569;">
                                Chuyến xe của quý khách tuyến <strong style="color: #0f172a;">{{ $routeName }}</strong> chuẩn bị xuất bến trong <strong>khoảng 45 phút tới</strong>. Vui lòng có mặt tại điểm đón trước <strong>15–20 phút</strong> để Bác tài và Lơ xe hướng dẫn sắp xếp hành lý và lên xe đúng giờ.
                            </p>

                            <!-- DEPARTURE HERO BOX -->
                            <table role="presentation" class="hero-box" width="100%" cellspacing="0" cellpadding="0" style="background: linear-gradient(135deg, #fef2f2 0%, #fff7ed 100%); border: 2px dashed #f87171; border-radius: 20px; padding: 22px 20px; text-align: center; margin: 8px 0 22px 0;">
                                <tr>
                                    <td align="center">
                                        <span style="display: block; font-size: 11px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px;">
                                            GIỜ XUẤT BẾN CHÍNH XÁC
                                        </span>
                                        <div style="font-family: 'SF Pro Display', -apple-system, 'Segoe UI', Roboto, 'Courier New', monospace; font-size: 30px; font-weight: 900; color: #dc2626; margin: 4px 0 10px 0;">
                                            ⏱️ {{ $departureTime }}
                                        </div>
                                        <div style="margin-top: 6px;">
                                            <span style="display: inline-block; background-color: #fee2e2; border: 1.5px solid #fca5a5; color: #991b1b; font-size: 13.5px; font-weight: 800; padding: 6px 16px; border-radius: 12px;">
                                                Mã vé: #{{ $bookingCode }} • Ghế: <strong style="color: #7f1d1d;">{{ $seatNumbers }}</strong>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- QUICK DRIVER CONTACT BAR -->
                            <div style="background-color: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 16px; padding: 14px 18px; margin: 0 0 20px 0;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td>
                                            <span style="display: block; font-size: 11px; font-weight: 800; color: #1e40af; text-transform: uppercase; margin-bottom: 2px;">
                                                👨‍✈️ BÁC TÀI PHỤ TRÁCH: {{ $driverName }}
                                            </span>
                                            <span style="font-size: 13px; font-weight: 700; color: #1e3a8a;">
                                                Hotline xe: <a href="tel:{{ $driverPhone }}" style="color: #2563eb; font-weight: 900; text-decoration: none;">{{ $driverPhone }}</a>
                                            </span>
                                        </td>
                                        <td align="right">
                                            <a href="tel:{{ $driverPhone }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; font-size: 12px; font-weight: 800; padding: 8px 16px; border-radius: 10px; text-decoration: none; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);">
                                                📞 Gọi Bác Tài
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- 4. TRIP DETAILS TABLE -->
                    <tr>
                        <td style="padding: 0 30px 24px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 14px 20px; background-color: #f1f5f9; border-bottom: 1px solid #e2e8f0;">
                                        <h3 style="margin: 0; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #334155; letter-spacing: 0.5px;">
                                            📋 THÔNG TIN ĐIỂM ĐÓN & XE CHẠY
                                        </h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 13.5px; line-height: 2.0;">
                                            <tr>
                                                <td width="38%" style="color: #64748b; font-weight: 600;">Mã chuyến:</td>
                                                <td width="62%" style="color: #0f172a; font-weight: 800; font-family: monospace;">{{ $tripCode }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Điểm đón xe:</td>
                                                <td style="color: #dc2626; font-weight: 800;">📍 {{ $pickupStop }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Biển số & Loại xe:</td>
                                                <td style="color: #0f172a; font-weight: 800; font-family: monospace;">🚌 {{ $busPlate }} ({{ $busType }})</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Vị trí chỗ ngồi:</td>
                                                <td style="color: #047857; font-weight: 800;">💺 {{ $seatNumbers }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 5. CALL TO ACTION BUTTON -->
                    <tr>
                        <td style="padding: 0 30px 24px 30px; text-align: center;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $ticketUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 900; padding: 14px 32px; border-radius: 14px; box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35); text-transform: uppercase; letter-spacing: 0.5px;">
                                            📱 Mở Vé Điện Tử & Mã QR Check-in →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 12px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                                Khi lên xe, bạn chỉ cần đưa mã QR trên điện thoại cho Lơ xe quét để vào ghế ngồi.
                            </p>
                        </td>
                    </tr>

                    <!-- 6. TRAVEL NOTES -->
                    <tr>
                        <td style="padding: 0 30px 24px 30px;">
                            <div style="background-color: #fefce8; border-left: 4px solid #eab308; border-radius: 12px; padding: 14px 18px; border: 1px solid #fef08a; border-left-width: 4px;">
                                <strong style="color: #854d0e; font-size: 12.5px; display: block; margin-bottom: 6px;">
                                    💡 LƯU Ý QUAN TRỌNG KHI LÊN XE:
                                </strong>
                                <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: #713f12; line-height: 1.65;">
                                    <li>Lơ xe sẽ gọi điện thoại theo số đã đăng ký nếu sắp đến giờ chạy mà chưa thấy quý khách check-in.</li>
                                    <li>Vui lòng giữ điện thoại luôn mở chuông và sẵn sàng nhận cuộc gọi.</li>
                                    <li>Hành lý xách tay miễn cước 20kg. Không mang theo chất dễ cháy nổ hoặc hàng cấm.</li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- 7. FOOTER -->
                    <tr>
                        <td class="footer-padding" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px 32px; text-align: center;">
                            <p style="margin: 0 0 6px 0; font-size: 12.5px; font-weight: 800; color: #0f172a; text-transform: uppercase;">
                                HỆ THỐNG VẬN TẢI CÔNG NGHỆ CAO NHAXEKIENTRUC
                            </p>
                            <p style="margin: 0 0 10px 0; font-size: 11.5px; color: #64748b; line-height: 1.5;">
                                📍 Bến xe Trung tâm Đà Nẵng, 185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng
                            </p>
                            <div style="font-size: 12px; color: #334155; margin-bottom: 12px;">
                                📞 Tổng đài 24/7: <a href="tel:19006067" style="color: #dc2626; font-weight: 900; text-decoration: none;">1900 6067</a> &nbsp;|&nbsp; 
                                📱 Hotline: <a href="tel:0905123456" style="color: #dc2626; font-weight: 800; text-decoration: none;">0905 123 456</a>
                            </div>
                            <p style="margin: 0; font-size: 10.5px; color: #94a3b8; line-height: 1.4;">
                                © 2026 nhaxekientruc Terminal Vietnam. Toàn bộ bản quyền được bảo lưu.<br>
                                Chúc Quý khách có một chuyến đi an toàn, thoải mái và vạn dặm bình an!
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
