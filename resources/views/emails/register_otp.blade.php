<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Mã Xác Thực OTP - nhaxekientruc Terminal</title>
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .content-padding { padding: 24px 18px !important; }
            .header-padding { padding: 28px 18px !important; }
            .otp-code { font-size: 32px !important; letter-spacing: 6px !important; }
            .footer-padding { padding: 20px 18px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 32px 12px;">
        <tr>
            <td align="center">
                <!-- MAIN CARD (MAX 580PX) -->
                <table role="presentation" class="email-container" width="100%" cellspacing="0" cellpadding="0" style="max-width: 580px; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0;">
                    
                    <!-- 1. TOP BRAND HEADER -->
                    <tr>
                        <td class="header-padding" style="background: linear-gradient(135deg, #b91c1c 0%, #dc2626 50%, #ea580c 100%); padding: 32px 28px; text-align: center; border-bottom: 3px solid #f59e0b;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center">
                                        <!-- Bus Badge Icon -->
                                        <div style="display: inline-block; width: 48px; height: 48px; line-height: 48px; border-radius: 16px; background-color: rgba(255, 255, 255, 0.2); border: 1.5px solid rgba(255, 255, 255, 0.4); font-size: 24px; text-align: center; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                            🚌
                                        </div>
                                        <!-- Brand Title -->
                                        <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase;">
                                            nhaxekientruc
                                        </h1>
                                        <!-- Subtitle Badge -->
                                        <div style="margin-top: 6px;">
                                            <span style="display: inline-block; background-color: rgba(0, 0, 0, 0.25); color: #fef08a; font-size: 10.5px; font-weight: 800; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(254, 240, 138, 0.5); letter-spacing: 0.8px; text-transform: uppercase;">
                                                ĐÀ NẴNG ↔ LIÊN TỈNH TOÀN QUỐC
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 2. SUB-BANNER TITLE -->
                    <tr>
                        <td style="background-color: #fff7ed; padding: 12px 24px; text-align: center; border-bottom: 1px solid #fed7aa;">
                            <span style="color: #c2410c; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                                🔐 MÃ XÁC THỰC BẢO MẬT ĐĂNG KÝ TÀI KHOẢN
                            </span>
                        </td>
                    </tr>

                    <!-- 3. BODY CONTENT -->
                    <tr>
                        <td class="content-padding" style="padding: 32px 32px 20px 32px;">
                            <p style="margin: 0 0 12px 0; font-size: 15px; font-weight: 700; color: #0f172a;">
                                Xin chào bạn,
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.65; color: #475569;">
                                Cảm ơn bạn đã lựa chọn hệ thống đặt vé xe công nghệ cao <strong style="color: #dc2626;">nhaxekientruc Terminal</strong>. Dưới đây là mã xác thực 6 số (OTP) dùng để hoàn tất xác minh tài khoản của bạn:
                            </p>

                            <!-- OTP CODE BOX (HERO DISPLAY) -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 20px 0 24px 0;">
                                <tr>
                                    <td align="center" style="background: linear-gradient(135deg, #fef2f2 0%, #fff7ed 100%); border: 2px dashed #f87171; border-radius: 20px; padding: 26px 20px; text-align: center; box-shadow: inset 0 2px 4px rgba(220, 38, 38, 0.03);">
                                        <span style="display: block; font-size: 11px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px;">
                                            MÃ XÁC THỰC OTP CỦA BẠN LÀ
                                        </span>
                                        <div class="otp-code" style="font-family: 'SF Pro Display', -apple-system, 'Segoe UI', Roboto, 'Courier New', monospace; font-size: 40px; font-weight: 900; letter-spacing: 10px; color: #dc2626; text-indent: 10px; margin: 4px 0;">
                                            {{ $otp }}
                                        </div>
                                        <div style="margin-top: 10px;">
                                            <span style="display: inline-block; background-color: #fee2e2; color: #991b1b; font-size: 11.5px; font-weight: 700; padding: 4px 14px; border-radius: 12px; border: 1px solid #fecaca;">
                                                ⏱️ Mã có hiệu lực trong vòng <strong>10 phút</strong>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- SECURITY ADVICE -->
                            <div style="background-color: #f8fafc; border-left: 4px solid #dc2626; border-radius: 12px; padding: 14px 16px; margin: 0 0 20px 0; border: 1px solid #e2e8f0; border-left-width: 4px;">
                                <p style="margin: 0; font-size: 12.5px; line-height: 1.6; color: #475569;">
                                    <strong style="color: #dc2626;">🛡️ Lưu ý bảo mật:</strong> Tuyệt đối không chia sẻ mã này cho bất kỳ ai, kể cả nhân viên tự xưng là tổng đài viên. Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email hoặc liên hệ tổng đài để được hỗ trợ.
                                </p>
                            </div>

                            <!-- BENEFIT PILLS -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 16px 0 8px 0; background-color: #fcfcfd; border-radius: 16px; border: 1px solid #f1f5f9; padding: 12px 14px;">
                                <tr>
                                    <td width="33%" align="center" style="font-size: 11.5px; color: #475569; font-weight: 600; padding: 6px 4px;">
                                        🎓 <strong>Sinh viên:</strong> Vé 0đ
                                    </td>
                                    <td width="33%" align="center" style="font-size: 11.5px; color: #475569; font-weight: 600; padding: 6px 4px; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">
                                        💺 <strong>Giữ 100%:</strong> Chỗ thật
                                    </td>
                                    <td width="33%" align="center" style="font-size: 11.5px; color: #475569; font-weight: 600; padding: 6px 4px;">
                                        📱 <strong>Vé QR:</strong> Quét lên xe
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- 4. FOOTER -->
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
                                Email được gửi tự động, vui lòng không phản hồi trực tiếp vào địa chỉ này.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
