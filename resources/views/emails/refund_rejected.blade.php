<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Thông Báo Từ Chối Hoàn Tiền Vé - nhaxekientruc</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-padding { padding: 22px 16px !important; }
            .header-padding { padding: 26px 18px !important; }
            .reason-box { padding: 18px 14px !important; }
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
                    
                    <!-- 1. TOP BRAND HEADER (WARNING CRIMSON) -->
                    <tr>
                        <td class="header-padding" style="background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 50%, #c2410c 100%); padding: 32px 28px; text-align: center; border-bottom: 3px solid #f59e0b;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <!-- Warning Badge Icon -->
                                        <div style="display: inline-block; width: 50px; height: 50px; line-height: 50px; border-radius: 16px; background-color: rgba(255, 255, 255, 0.2); border: 1.5px solid rgba(255, 255, 255, 0.4); font-size: 26px; text-align: center; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                            ⚠️
                                        </div>
                                        <!-- Brand Title -->
                                        <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase;">
                                            nhaxekientruc
                                        </h1>
                                        <!-- Tagline Badge -->
                                        <div style="margin-top: 6px;">
                                            <span style="display: inline-block; background-color: rgba(0, 0, 0, 0.25); color: #fecaca; font-size: 11px; font-weight: 800; padding: 4px 14px; border-radius: 20px; border: 1px solid rgba(254, 202, 202, 0.5); letter-spacing: 0.8px; text-transform: uppercase;">
                                                THÔNG BÁO TỪ CHỐI HOÀN TIỀN VÉ
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 2. ROUTE HIGHLIGHT STRIP -->
                    <tr>
                        <td style="background-color: #fff1f2; padding: 12px 24px; text-align: center; border-bottom: 1px solid #fecdd3;">
                            <span style="color: #9f1239; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px;">
                                🎫 Mã Vé #{{ $bookingCode }} • {{ $routeName }}
                            </span>
                        </td>
                    </tr>

                    <!-- 3. GREETING & REASON HERO -->
                    <tr>
                        <td class="content-padding" style="padding: 28px 30px 16px 30px;">
                            <p style="margin: 0 0 10px 0; font-size: 16px; font-weight: 800; color: #0f172a;">
                                Kính gửi Quý khách: <span style="color: #b91c1c;">{{ $customerName }}</span>,
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.65; color: #475569;">
                                Ban Quản Trị <strong>nhaxekientruc Terminal</strong> đã xem xét yêu cầu hoàn tiền cho đơn đặt vé điện tử <strong>#{{ $bookingCode }}</strong> của bạn. Rất tiếc, yêu cầu này <strong>không được phê duyệt</strong> do không đáp ứng đúng điều kiện quy chế hoàn hủy vé của nhà xe.
                            </p>

                            <!-- REASON HERO BOX -->
                            <table role="presentation" class="reason-box" width="100%" cellspacing="0" cellpadding="0" style="background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%); border: 2px dashed #fca5a5; border-radius: 20px; padding: 20px; margin: 8px 0 22px 0;">
                                <tr>
                                    <td>
                                        <span style="display: block; font-size: 11px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                            📌 LÝ DO TỪ CHỐI TỪ BAN QUẢN TRỊ:
                                        </span>
                                        <div style="font-size: 15px; font-weight: 700; color: #b91c1c; line-height: 1.6;">
                                            "{{ $rejectReason }}"
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 4. DETAILS TABLE -->
                    <tr>
                        <td style="padding: 0 30px 24px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 14px 20px; background-color: #f1f5f9; border-bottom: 1px solid #e2e8f0;">
                                        <h3 style="margin: 0; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #334155; letter-spacing: 0.5px;">
                                            📋 THÔNG TIN VÉ LIÊN QUAN
                                        </h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 13.5px; line-height: 2.0;">
                                            <tr>
                                                <td width="38%" style="color: #64748b; font-weight: 600;">Mã vé:</td>
                                                <td width="62%" style="color: #0f172a; font-weight: 800; font-family: monospace;">#{{ $bookingCode }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Tuyến hành trình:</td>
                                                <td style="color: #0f172a; font-weight: 700;">{{ $routeName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Giờ khởi hành:</td>
                                                <td style="color: #0f172a;">{{ $departureTime }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-weight: 600;">Thời gian xử lý:</td>
                                                <td style="color: #0f172a;">{{ $processedAt }}</td>
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
                                        <a href="{{ $ticketUrl }}" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 900; padding: 14px 32px; border-radius: 14px; box-shadow: 0 6px 16px rgba(185, 28, 28, 0.35); text-transform: uppercase; letter-spacing: 0.5px;">
                                            📲 Xem Lại Trạng Thái Vé →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 12px 0 0 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                                Nếu quý khách có bất kỳ thắc mắc hoặc cần giải thích thêm, vui lòng liên hệ trực tiếp tổng đài <strong style="color: #dc2626;">1900 6067</strong>.
                            </p>
                        </td>
                    </tr>

                    <!-- 6. FOOTER -->
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
                                © 2026 nhaxekientruc Terminal Vietnam. Toàn bộ bản quyền được bảo lưu.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
