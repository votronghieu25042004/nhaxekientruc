<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Xác Nhận Đặt Vé & Thẻ Lên Xe Điện Tử - nhaxekientruc</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-padding { padding: 22px 16px !important; }
            .header-padding { padding: 26px 18px !important; }
            .ticket-box { padding: 18px 14px !important; }
            .qr-image { width: 160px !important; height: 160px !important; }
            .route-title { font-size: 16px !important; }
            .footer-padding { padding: 20px 16px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 32px 12px;">
        <tr>
            <td align="center">
                <!-- MAIN CARD (MAX 620PX) -->
                <table role="presentation" class="email-container" width="100%" cellspacing="0" cellpadding="0" style="max-width: 620px; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 12px 36px rgba(0, 0, 0, 0.07), 0 2px 6px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0;">
                    
                    <!-- 1. TOP BRAND HEADER -->
                    <tr>
                        <td class="header-padding" style="background: linear-gradient(135deg, #b91c1c 0%, #dc2626 50%, #ea580c 100%); padding: 32px 28px; text-align: center; border-bottom: 3px solid #f59e0b;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <!-- Bus Badge Icon -->
                                        <div style="display: inline-block; width: 50px; height: 50px; line-height: 50px; border-radius: 16px; background-color: rgba(255, 255, 255, 0.2); border: 1.5px solid rgba(255, 255, 255, 0.4); font-size: 26px; text-align: center; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                            🎫
                                        </div>
                                        <!-- Brand Title -->
                                        <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase;">
                                            nhaxekientruc
                                        </h1>
                                        <!-- Tagline Badge -->
                                        <div style="margin-top: 6px;">
                                            <span style="display: inline-block; background-color: rgba(0, 0, 0, 0.25); color: #fef08a; font-size: 11px; font-weight: 800; padding: 4px 14px; border-radius: 20px; border: 1px solid rgba(254, 240, 138, 0.5); letter-spacing: 0.8px; text-transform: uppercase;">
                                                ✓ XÁC NHẬN ĐẶT VÉ THÀNH CÔNG • THẺ LÊN XE ĐIỆN TỬ
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 2. ROUTE HIGHLIGHT STRIP -->
                    <tr>
                        <td style="background-color: #fff7ed; padding: 14px 24px; text-align: center; border-bottom: 1px solid #fed7aa;">
                            <span class="route-title" style="color: #9a3412; font-size: 15px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">
                                🚌 {{ $routeName }}
                            </span>
                        </td>
                    </tr>

                    <!-- 3. GREETING & TICKET HERO -->
                    <tr>
                        <td class="content-padding" style="padding: 28px 30px 16px 30px;">
                            <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: 800; color: #0f172a;">
                                Kính gửi Quý khách: <span style="color: #dc2626;">{{ $customerName }}</span>,
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.65; color: #475569;">
                                Đơn đặt vé của bạn đã được xác nhận thành công trên hệ thống <strong>nhaxekientruc Terminal</strong>. Khi lên xe, bạn chỉ cần mở email này và đưa mã QR bên dưới cho Bác tài hoặc Lơ xe quét để nhận chỗ.
                            </p>

                            <!-- BOARDING PASS CARD (HERO QR BOX) -->
                            <table role="presentation" class="ticket-box" width="100%" cellspacing="0" cellpadding="0" style="background: linear-gradient(135deg, #fef2f2 0%, #fff7ed 100%); border: 2px dashed #f87171; border-radius: 20px; padding: 24px 20px; text-align: center; margin: 8px 0 24px 0; box-shadow: inset 0 2px 4px rgba(220, 38, 38, 0.03);">
                                <tr>
                                    <td align="center">
                                        <!-- Ticket Code Badge -->
                                        <span style="display: inline-block; font-size: 11px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px;">
                                            MÃ ĐẶT VÉ ĐIỆN TỬ
                                        </span>
                                        <div style="font-family: 'SF Pro Display', -apple-system, 'Segoe UI', Roboto, 'Courier New', monospace; font-size: 30px; font-weight: 900; letter-spacing: 3px; color: #dc2626; margin: 4px 0 10px 0;">
                                            #{{ $bookingCode }}
                                        </div>

                                        <!-- Seat Number Pill -->
                                        <div style="margin-bottom: 16px;">
                                            <span style="display: inline-block; background-color: #fee2e2; border: 1.5px solid #fca5a5; color: #991b1b; font-size: 14px; font-weight: 800; padding: 6px 18px; border-radius: 14px; box-shadow: 0 2px 6px rgba(220, 38, 38, 0.08);">
                                                💺 VỊ TRÍ GHẾ: <strong style="color: #7f1d1d; font-size: 15px;">{{ $seatNumbers }}</strong>
                                            </span>
                                        </div>

                                        <!-- QR Code Box -->
                                        <div style="display: inline-block; padding: 12px; background-color: #ffffff; border-radius: 20px; border: 2px solid #fecaca; box-shadow: 0 6px 18px rgba(220, 38, 38, 0.12);">
                                            <img class="qr-image" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($qrToken ?? $bookingCode) }}" width="190" height="190" alt="Mã QR Soát Vé" style="display: block; border-radius: 12px;" />
                                        </div>

                                        <!-- Scan Instructions -->
                                        <p style="margin: 12px 0 2px 0; font-size: 12px; font-weight: 800; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.5px;">
                                            ⚡ MÃ QR CHECK-IN LÊN XE NHANH CHÓNG
                                        </p>
                                        <p style="margin: 0; font-size: 11.5px; color: #64748b; line-height: 1.4;">
                                            (Đưa mã QR trên màn hình điện thoại cho Bác tài hoặc Lơ xe quét trong 1 giây)
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 4. TRIP DETAILS TABLE (CARD STYLE) -->
                    <tr>
                        <td style="padding: 0 30px 24px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 16px 20px; background-color: #f1f5f9; border-bottom: 1px solid #e2e8f0;">
                                        <h3 style="margin: 0; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #334155; letter-spacing: 0.5px;">
                                            📋 THÔNG TIN HÀNH TRÌNH CHI TIẾT
                                        </h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 13.5px; line-height: 2.0;">
                                            <tr>
                                                <td width="38%" style="color: #64748b; font-weight: 600;">Tuyến đường:</td>
                                                <td width="62%" style="color: #dc2626; font-weight: 800;">{{ $routeName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Giờ khởi hành:</td>
                                                <td style="color: #047857; font-weight: 900; font-size: 14px;">⏱️ {{ $departureTime }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Điểm đón xe:</td>
                                                <td style="color: #0f172a; font-weight: 700;">📍 {{ $pickupStop }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Điểm trả khách:</td>
                                                <td style="color: #0f172a; font-weight: 700;">🏁 {{ $dropoffStop }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Biển số & Loại xe:</td>
                                                <td style="color: #0f172a; font-weight: 800; font-family: monospace;">🚌 {{ $busPlate }} ({{ $busType }})</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Bác tài phụ trách:</td>
                                                <td style="color: #0f172a; font-weight: 700;">👨‍✈️ {{ $driverName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Tổng cước vé:</td>
                                                <td style="color: #0f172a; font-weight: 800;">{{ $totalAmount }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Đã thanh toán:</td>
                                                <td style="color: #047857; font-weight: 900;">✓ {{ $paidAmount }} (Đã xác nhận)</td>
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
                                            📲 Mở Thẻ Lên Xe Trực Tuyến & Tải Vé →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 12px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                                💡 Hệ thống AI sẽ tự động gửi email nhắc lịch và biển số xe trước giờ chạy <strong>45 phút</strong>.
                            </p>
                        </td>
                    </tr>

                    <!-- 6. PASSENGER BENEFITS STRIP -->
                    <tr>
                        <td style="padding: 0 30px 24px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border-radius: 16px; border: 1px solid #e2e8f0; padding: 12px 14px;">
                                <tr>
                                    <td width="33%" align="center" style="font-size: 11px; color: #475569; font-weight: 600; padding: 6px 4px;">
                                        🧳 <strong>Hành lý:</strong> Free 20kg
                                    </td>
                                    <td width="33%" align="center" style="font-size: 11px; color: #475569; font-weight: 600; padding: 6px 4px; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">
                                        ❄️ <strong>Tiện ích:</strong> Wifi & Sạc USB
                                    </td>
                                    <td width="33%" align="center" style="font-size: 11px; color: #475569; font-weight: 600; padding: 6px 4px;">
                                        🛡️ <strong>Bảo hiểm:</strong> Hành khách 100%
                                    </td>
                                </tr>
                            </table>
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
                                Chúc Quý khách có một hành trình an toàn, êm ái và vạn dặm bình an!
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
