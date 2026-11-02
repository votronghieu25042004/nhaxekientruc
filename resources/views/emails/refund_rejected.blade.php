<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Báo Từ Chối Yêu Cầu Hoàn Tiền Vé - Nhà Xe Kiến Trúc</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0f172a; padding: 30px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); border: 1px solid #e2e8f0;">
                    
                    <!-- HEADER BANNER: CRIMSON WARNING GRADIENT -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 50%, #b91c1c 100%); padding: 36px 30px; text-align: center; border-bottom: 3px solid #ef4444;">
                            <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; border-radius: 18px; background-color: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.3); font-size: 28px; margin-bottom: 12px;">
                                ⚠️
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase;">
                                NHÀ XE KIẾN TRÚC TERMINAL
                            </h1>
                            <div style="display: inline-block; margin-top: 8px; background-color: rgba(255, 255, 255, 0.2); border: 1px solid #fca5a5; border-radius: 20px; padding: 5px 16px;">
                                <span style="color: #ffffff; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                                    THÔNG BÁO TỪ CHỐI HOÀN TIỀN VÉ
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- GREETING -->
                    <tr>
                        <td style="padding: 32px 32px 16px 32px;">
                            <p style="margin: 0 0 14px 0; font-size: 16px; font-weight: 800; color: #0f172a;">
                                Kính gửi Quý khách: <span style="color: #b91c1c;">{{ $customerName }}</span>,
                            </p>
                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Ban Quản Trị <strong>Nhà Xe Kiến Trúc</strong> đã xem xét yêu cầu hoàn tiền cho đơn đặt vé điện tử <strong>#{{ $bookingCode }}</strong> của bạn. Rất tiếc, yêu cầu này <strong>không được phê duyệt</strong> do không đáp ứng đúng điều kiện quy chế hoàn hủy vé của nhà xe.
                            </p>

                            <!-- REASON BOX -->
                            <div style="background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%); border: 2px dashed #fca5a5; border-radius: 18px; padding: 22px; margin: 20px 0;">
                                <span style="display: block; font-size: 11px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                                    📌 LÝ DO TỪ CHỐI TỪ BAN QUẢN TRỊ:
                                </span>
                                <div style="font-size: 15px; font-weight: 700; color: #b91c1c; line-height: 1.6;">
                                    "{{ $rejectReason }}"
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- DETAILS TABLE -->
                    <tr>
                        <td style="padding: 0 32px 24px 32px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 18px; padding: 20px;">
                                <h3 style="margin: 0 0 14px 0; font-size: 12px; font-weight: 800; text-transform: uppercase; color: #334155; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                                    📋 Thông Tin Vé Xe Liên Quan
                                </h3>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 13px; line-height: 1.9;">
                                    <tr>
                                        <td width="40%" style="color: #64748b; font-weight: 600;">Mã vé:</td>
                                        <td width="60%" style="color: #0f172a; font-weight: 800; font-family: monospace;">#{{ $bookingCode }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;">Tuyến đường:</td>
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
                            </div>
                        </td>
                    </tr>

                    <!-- CTA BUTTON -->
                    <tr>
                        <td style="padding: 0 32px 28px 32px; text-align: center;">
                            <a href="{{ $ticketUrl }}" style="display: inline-block; background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%); color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 800; padding: 12px 28px; border-radius: 12px; box-shadow: 0 4px 12px rgba(185, 28, 28, 0.3); text-transform: uppercase;">
                                📲 Xem Lại Trạng Thái Vé
                            </a>
                            <p style="margin: 12px 0 0 0; font-size: 12px; color: #64748b;">
                                Nếu quý khách có bất kỳ thắc mắc hoặc cần khiếu nại, vui lòng liên hệ ngay tổng đài <strong>1900 6067</strong> để được hỗ trợ trực tiếp.
                            </p>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background-color: #f1f5f9; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11px; color: #64748b; line-height: 1.6;">
                            <p style="margin: 0; font-weight: 700; color: #334155;">NHAXEKIENTRUC TERMINAL VIỆT NAM</p>
                            <p style="margin: 4px 0 0 0;">Tổng đài hỗ trợ 24/7: <strong>1900 6067</strong> • Hotline: <strong>0905 123 456</strong></p>
                            <p style="margin: 4px 0 0 0;">Văn phòng: 120 Điện Biên Phủ, Thanh Khê, Đà Nẵng</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
