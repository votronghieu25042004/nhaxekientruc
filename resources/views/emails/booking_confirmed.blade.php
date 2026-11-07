<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Xác Nhận Đặt Vé & Thẻ Lên Xe Điện Tử - Trường ĐH Kiến Trúc Đà Nẵng</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; border-radius: 0 !important; }
            .content-padding { padding: 20px 16px !important; }
            .header-padding { padding: 20px 16px !important; }
            .ticket-box { padding: 18px 12px !important; }
            .qr-image { width: 150px !important; height: 150px !important; }
            .route-title { font-size: 14px !important; }
            .footer-padding { padding: 20px 16px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 28px 10px;">
        <tr>
            <td align="center">
                <!-- MAIN CARD (MAX 600PX) -->
                <table role="presentation" class="email-container" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05), 0 2px 6px rgba(0, 0, 0, 0.03); border: 1px solid #e2e8f0;">
                    
                    <!-- 1. TOP BRAND HEADER VỚI LOGO TRƯỜNG ĐH KIẾN TRÚC -->
                    <tr>
                        <td class="header-padding" style="background-color: #ffffff; padding: 26px 28px 20px 28px; text-align: center; border-bottom: 2px solid #b91c1c;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <!-- Logo Trường ĐH Kiến Trúc -->
                                        <img src="https://raw.githubusercontent.com/votronghieu25042004/bushub-booking/main/public/images/logo.png" width="68" height="68" alt="Logo Đại Học Kiến Trúc Đà Nẵng" style="display: block; margin: 0 auto 10px auto; width: 68px; height: 68px; object-fit: contain; border: 0;" />
                                        
                                        <!-- Tên Trường -->
                                        <div style="font-size: 13px; font-weight: 800; color: #b91c1c; text-transform: uppercase; letter-spacing: 1px; line-height: 1.3;">
                                            TRƯỜNG ĐẠI HỌC KIẾN TRÚC ĐÀ NẴNG
                                        </div>
                                        
                                        <!-- Tên Hệ Thống Nhà Xe -->
                                        <div style="font-size: 19px; font-weight: 900; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px;">
                                            NHAXEKIENTRUC TERMINAL
                                        </div>
                                        
                                        <!-- Tagline Xác Nhận -->
                                        <div style="margin-top: 5px;">
                                            <span style="display: inline-block; background-color: #fef2f2; color: #991b1b; font-size: 10.5px; font-weight: 800; padding: 3px 12px; border-radius: 12px; border: 1px solid #fecaca; letter-spacing: 0.5px; text-transform: uppercase;">
                                                XÁC NHẬN ĐẶT VÉ THÀNH CÔNG - THẺ LÊN XE ĐIỆN TỬ
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 2. ROUTE HIGHLIGHT BANNER (KHÔNG DÙNG EMOJI) -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 12px 24px; text-align: center; border-bottom: 1px solid #e2e8f0;">
                            <span class="route-title" style="color: #b91c1c; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.8px;">
                                [TUYẾN XE]: {{ $routeName }}
                            </span>
                        </td>
                    </tr>

                    <!-- 3. NỘI DUNG CHÍNH & VÉ ĐIỆN TỬ -->
                    <tr>
                        <td class="content-padding" style="padding: 28px 30px 16px 30px;">
                            <p style="margin: 0 0 8px 0; font-size: 15px; font-weight: 800; color: #0f172a;">
                                Kính gửi Quý khách: <span style="color: #b91c1c;">{{ $customerName }}</span>,
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 13.5px; line-height: 1.65; color: #475569;">
                                Đơn đặt vé của bạn đã được xác nhận thành công trên hệ thống <strong>nhaxekientruc Terminal</strong>. Khi lên xe, bạn chỉ cần mở email này và đưa mã QR bên dưới cho Bác tài hoặc Lơ xe quét để nhận chỗ.
                            </p>

                            <!-- KHUNG THẺ LÊN XE & MÃ QR (HERO BOARDING PASS) -->
                            <table role="presentation" class="ticket-box" width="100%" cellspacing="0" cellpadding="0" style="background-color: #fef2f2; border: 2px dashed #f87171; border-radius: 16px; padding: 22px 18px; text-align: center; margin: 6px 0 24px 0;">
                                <tr>
                                    <td align="center">
                                        <span style="display: block; font-size: 11px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px;">
                                            MÃ ĐẶT VÉ ĐIỆN TỬ
                                        </span>
                                        <div style="font-family: 'SF Pro Display', -apple-system, 'Segoe UI', Roboto, 'Courier New', monospace; font-size: 28px; font-weight: 900; letter-spacing: 2.5px; color: #dc2626; margin: 4px 0 10px 0;">
                                            #{{ $bookingCode }}
                                        </div>

                                        <!-- Vị trí ghế nổi bật -->
                                        <div style="margin-bottom: 14px;">
                                            <span style="display: inline-block; background-color: #ffffff; border: 1.5px solid #fca5a5; color: #991b1b; font-size: 13.5px; font-weight: 800; padding: 6px 16px; border-radius: 12px;">
                                                VỊ TRÍ GHẾ: <strong style="color: #7f1d1d; font-size: 14.5px;">{{ $seatNumbers }}</strong>
                                            </span>
                                        </div>

                                        <!-- Mã QR Code Check-in -->
                                        <div style="display: inline-block; background-color: #ffffff; padding: 10px; border-radius: 14px; border: 1px solid #fecaca; box-shadow: 0 4px 10px rgba(0,0,0,0.06);">
                                            <img 
                                                class="qr-image" 
                                                src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($qrToken) }}" 
                                                alt="Mã QR Check-in" 
                                                width="170" 
                                                height="170" 
                                                style="display: block; width: 170px; height: 170px; border-radius: 8px;" 
                                            />
                                        </div>

                                        <p style="margin: 12px 0 0 0; font-size: 12px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px;">
                                            MÃ QR CHECK-IN LÊN XE NHANH CHÓNG
                                        </p>
                                        <p style="margin: 3px 0 0 0; font-size: 11px; color: #64748b;">
                                            (Đưa mã QR trên màn hình điện thoại cho Bác tài hoặc Lơ xe quét trong 1 giây)
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- BẢNG THÔNG TIN CHI TIẾT CHUYẾN XE (RÕ RÀNG, CHUYÊN NGHIỆP) -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; margin-bottom: 24px; border-collapse: collapse;">
                                <tr>
                                    <td colspan="2" style="background-color: #f8fafc; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                                        THÔNG TIN HÀNH TRÌNH CHI TIẾT
                                    </td>
                                </tr>
                                <tr>
                                    <td width="38%" style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; font-weight: 600;">Tuyến đường:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12.5px; color: #0f172a; font-weight: 800;">{{ $routeName }}</td>
                                </tr>
                                <tr style="background-color: #fafbfc;">
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; font-weight: 600;">Giờ khởi hành:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12.5px; color: #b91c1c; font-weight: 800;">{{ $departureTime }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; font-weight: 600;">Điểm đón khách:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #0f172a; font-weight: 700;">{{ $pickupStop }}</td>
                                </tr>
                                <tr style="background-color: #fafbfc;">
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; font-weight: 600;">Điểm trả khách:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #0f172a; font-weight: 700;">{{ $dropoffStop }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; font-weight: 600;">Biển số & Loại xe:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #0f172a; font-weight: 700;">
                                        <span style="font-family: monospace; background-color: #f1f5f9; padding: 2px 6px; border-radius: 4px;">{{ $busPlate }}</span> ({{ $busType }})
                                    </td>
                                </tr>
                                <tr style="background-color: #fafbfc;">
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; font-weight: 600;">Bác tài phụ trách:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #0f172a; font-weight: 700;">{{ $driverName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; font-weight: 600;">Tổng cước vé:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12.5px; color: #0f172a; font-weight: 800;">{{ $totalAmount }}</td>
                                </tr>
                                <tr style="background-color: #fafbfc;">
                                    <td style="padding: 10px 16px; font-size: 12px; color: #64748b; font-weight: 600;">Đã thanh toán:</td>
                                    <td style="padding: 10px 16px; font-size: 12.5px; color: #047857; font-weight: 900;">{{ $paidAmount }} (Đã xác nhận)</td>
                                </tr>
                            </table>

                            <!-- NÚT MỞ THẺ LÊN XE TRỰC TUYẾN -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 22px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $ticketUrl }}" target="_blank" style="display: inline-block; background-color: #b91c1c; color: #ffffff; font-size: 13.5px; font-weight: 800; text-decoration: none; padding: 13px 30px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 12px rgba(185, 28, 28, 0.25);">
                                            MỞ THẺ LÊN XE TRỰC TUYẾN & TẢI VÉ &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- TIỆN ÍCH DỊCH VỤ -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top: 10px; background-color: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; padding: 10px 14px;">
                                <tr>
                                    <td width="33%" align="center" style="font-size: 11px; color: #475569; font-weight: 600; padding: 4px;">
                                        <strong>Hành lý:</strong> Free 20kg
                                    </td>
                                    <td width="33%" align="center" style="font-size: 11px; color: #475569; font-weight: 600; padding: 4px; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">
                                        <strong>Tiện ích:</strong> Wifi 5G & Nước
                                    </td>
                                    <td width="33%" align="center" style="font-size: 11px; color: #475569; font-weight: 600; padding: 4px;">
                                        <strong>Bảo hiểm:</strong> Hành khách 100%
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- 4. CHÂN TRANG DOANH NGHIỆP TRƯỜNG ĐH KIẾN TRÚC -->
                    <tr>
                        <td class="footer-padding" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px 32px; text-align: center;">
                            <p style="margin: 0 0 4px 0; font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase;">
                                TRƯỜNG ĐẠI HỌC KIẾN TRÚC ĐÀ NẴNG - NHAXEKIENTRUC TERMINAL
                            </p>
                            <p style="margin: 0 0 10px 0; font-size: 11.5px; color: #64748b; line-height: 1.5;">
                                Bến xe Trung tâm Đà Nẵng: 185 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, TP. Đà Nẵng
                            </p>
                            <div style="font-size: 12px; color: #334155; margin-bottom: 12px;">
                                Tổng đài 24/7: <a href="tel:19006067" style="color: #b91c1c; font-weight: 900; text-decoration: none;">1900 6067</a> &nbsp;|&nbsp; 
                                Hotline xe: <a href="tel:0905123456" style="color: #b91c1c; font-weight: 800; text-decoration: none;">0905 123 456</a>
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
