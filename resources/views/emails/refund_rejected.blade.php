<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Thông Báo Kết Quả Hoàn Tiền Vé - Trường ĐH Kiến Trúc Đà Nẵng</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; border-radius: 0 !important; }
            .content-padding { padding: 20px 16px !important; }
            .header-padding { padding: 20px 16px !important; }
            .reason-box { padding: 18px 12px !important; }
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
                                        
                                        <!-- Tagline Thông Báo -->
                                        <div style="margin-top: 5px;">
                                            <span style="display: inline-block; background-color: #fef2f2; color: #991b1b; font-size: 10.5px; font-weight: 800; padding: 3px 12px; border-radius: 12px; border: 1px solid #fecaca; letter-spacing: 0.5px; text-transform: uppercase;">
                                                THÔNG BÁO TỪ CHỐI HOÀN TIỀN VÉ
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
                            <span style="color: #b91c1c; font-size: 13.5px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.8px;">
                                [MÃ VÉ: #{{ $bookingCode }}] - {{ $routeName }}
                            </span>
                        </td>
                    </tr>

                    <!-- 3. NỘI DUNG CHÍNH -->
                    <tr>
                        <td class="content-padding" style="padding: 28px 30px 16px 30px;">
                            <p style="margin: 0 0 8px 0; font-size: 15px; font-weight: 800; color: #0f172a;">
                                Kính gửi Quý khách: <span style="color: #b91c1c;">{{ $customerName }}</span>,
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 13.5px; line-height: 1.65; color: #475569;">
                                Ban Quản Trị hệ thống <strong>nhaxekientruc Terminal</strong> đã tiếp nhận và xem xét yêu cầu hoàn tiền cho đơn vé <strong>#{{ $bookingCode }}</strong> của quý khách. Sau khi đối chiếu với chính sách hủy đổi vé, chúng tôi rất tiếc phải thông báo yêu cầu hoàn tiền này <strong>chưa đủ điều kiện phê duyệt</strong>.
                            </p>

                            <!-- KHUNG LÝ DO TỪ CHỐI RÕ RÀNG (HERO BOX) -->
                            <table role="presentation" class="reason-box" width="100%" cellspacing="0" cellpadding="0" style="background-color: #fef2f2; border: 1.5px solid #fca5a5; border-left: 4px solid #dc2626; border-radius: 14px; padding: 18px 16px; margin: 6px 0 22px 0;">
                                <tr>
                                    <td>
                                        <span style="display: block; font-size: 11px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
                                            LÝ DO TỪ CHỐI TỪ BAN QUẢN TRỊ:
                                        </span>
                                        <div style="font-size: 13.5px; font-weight: 700; color: #7f1d1d; line-height: 1.6;">
                                            "{{ $rejectReason }}"
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- BẢNG THÔNG TIN VÉ LIÊN QUAN -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; margin-bottom: 22px; border-collapse: collapse;">
                                <tr>
                                    <td colspan="2" style="background-color: #f8fafc; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                                        THÔNG TIN VÉ XE
                                    </td>
                                </tr>
                                <tr>
                                    <td width="38%" style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; font-weight: 600;">Mã vé:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12.5px; color: #0f172a; font-weight: 800; font-family: monospace;">#{{ $bookingCode }}</td>
                                </tr>
                                <tr style="background-color: #fafbfc;">
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; font-weight: 600;">Tuyến đường:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12.5px; color: #0f172a; font-weight: 700;">{{ $routeName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; color: #64748b; font-weight: 600;">Giờ khởi hành:</td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12.5px; color: #b91c1c; font-weight: 800;">{{ $departureTime }}</td>
                                </tr>
                                <tr style="background-color: #fafbfc;">
                                    <td style="padding: 10px 16px; font-size: 12px; color: #64748b; font-weight: 600;">Thời gian xử lý:</td>
                                    <td style="padding: 10px 16px; font-size: 12px; color: #64748b; font-weight: 700;">{{ $processedAt }}</td>
                                </tr>
                            </table>

                            <!-- HƯỚNG DẪN KHIẾU NẠI / HỖ TRỢ -->
                            <div style="background-color: #f8fafc; border-radius: 12px; padding: 14px 16px; border: 1px solid #e2e8f0; margin-bottom: 22px;">
                                <p style="margin: 0; font-size: 12px; color: #475569; line-height: 1.6;">
                                    Nếu có bất kỳ thắc mắc hoặc cần khiếu nại thêm về lý do từ chối, quý khách vui lòng liên hệ trực tiếp tổng đài chăm sóc khách hàng 24/7 theo số <strong style="color: #b91c1c;">1900 6067</strong> và đọc mã vé <strong>#{{ $bookingCode }}</strong> để được nhân viên hỗ trợ trực tiếp.
                                </p>
                            </div>

                            <!-- NÚT XEM CHI TIẾT VÉ -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $ticketUrl }}" target="_blank" style="display: inline-block; background-color: #b91c1c; color: #ffffff; font-size: 13px; font-weight: 800; text-decoration: none; padding: 12px 28px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                                            XEM CHI TIẾT VÉ XE TRỰC TUYẾN &rarr;
                                        </a>
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
                                Cảm ơn bạn đã đồng hành cùng nhaxekientruc!
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
