<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Nhận Đặt Vé & Mã Vé QR Điện Tử - nhaxekientruc</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f0305; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #0f0305; padding: 30px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4); border: 1px solid #fecdd3;">
                    
                    <!-- HEADER BANNER: OBSIDIAN CRIMSON WITH GOLD ACCENTS -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #450a0a 0%, #7f1d1d 50%, #991b1b 100%); padding: 36px 30px; text-align: center; border-bottom: 3px solid #fbbf24;">
                            <div style="display: inline-block; width: 52px; height: 52px; line-height: 52px; border-radius: 18px; background-color: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); font-size: 26px; margin-bottom: 12px;">
                                🎫
                            </div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase;">
                                NHAXEKIENTRUC TERMINAL
                            </h1>
                            <div style="display: inline-block; margin-top: 8px; background-color: rgba(251, 191, 36, 0.2); border: 1px solid #fbbf24; border-radius: 20px; padding: 4px 14px;">
                                <span style="color: #fef08a; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                                    ✓ XÁC NHẬN ĐẶT VÉ THÀNH CÔNG
                                </span>
                            </div>
                        </td>
                    </tr>

                    <!-- GREETING -->
                    <tr>
                        <td style="padding: 32px 32px 16px 32px;">
                            <p style="margin: 0 0 14px 0; font-size: 16px; font-weight: 800; color: #0f172a;">
                                Kính gửi Quý khách: <span style="color: #dc2626;">{{ $customerName }}</span>,
                            </p>
                            <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                Cảm ơn bạn đã lựa chọn dịch vụ của <strong>nhaxekientruc</strong>. Đơn đặt vé của bạn đã được xác nhận thành công trên hệ thống. Dưới đây là thông tin chi tiết vé xe điện tử & mã check-in của bạn:
                            </p>

                            <!-- HIGHLIGHT BOX: TICKET CODE & QR LINK -->
                            <div style="background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%); border: 2px dashed #fca5a5; border-radius: 18px; padding: 22px; text-align: center; margin: 20px 0;">
                                <span style="display: block; font-size: 11px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">
                                    MÃ ĐẶT VÉ ĐIỆN TỬ
                                </span>
                                <div style="font-family: 'Courier New', Courier, monospace; font-size: 28px; font-weight: 900; letter-spacing: 4px; color: #b91c1c;">
                                    #{{ $bookingCode }}
                                </div>
                                <div style="margin-top: 10px;">
                                    <span style="display: inline-block; background-color: #fee2e2; color: #991b1b; font-size: 13px; font-weight: 800; padding: 6px 16px; border-radius: 14px;">
                                        Vị trí ghế: <strong style="color: #7f1d1d;">{{ $seatNumbers }}</strong>
                                    </span>
                                </div>

                                <!-- MÃ QR CHECK-IN LÊN XE TRỰC TIẾP TỪ EMAIL -->
                                <div style="margin: 18px auto 8px auto; text-align: center;">
                                    <div style="display: inline-block; padding: 12px; background-color: #ffffff; border-radius: 18px; border: 2px solid #fecdd3; box-shadow: 0 4px 15px rgba(220, 38, 38, 0.1);">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($qrToken ?? $bookingCode) }}" width="180" height="180" alt="Mã QR Check-in" style="display: block; border-radius: 10px;" />
                                    </div>
                                    <p style="margin: 8px 0 0 0; font-size: 11px; font-weight: 800; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.5px;">
                                        ⚡ MÃ QR CHECK-IN LÊN XE NHANH CHÓNG
                                    </p>
                                    <p style="margin: 3px 0 0 0; font-size: 11px; color: #64748b;">
                                        (Quý khách chỉ cần mở email này và đưa mã QR cho Bác tài hoặc Lơ xe quét khi lên xe)
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- TRIP DETAILS CARD -->
                    <tr>
                        <td style="padding: 0 32px 24px 32px;">
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 18px; padding: 22px;">
                                <h3 style="margin: 0 0 16px 0; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #334155; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                                    📋 Chi Tiết Chuyến Xe & Hành Trình
                                </h3>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size: 13px; line-height: 1.9;">
                                    <tr>
                                        <td width="38%" style="color: #64748b; font-weight: 600;">Tuyến đường:</td>
                                        <td width="62%" style="color: #b91c1c; font-weight: 800;">{{ $routeName }}</td>
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
                                        <td style="color: #64748b; font-weight: 600;">Điểm trả khách:</td>
                                        <td style="color: #0f172a; font-weight: 700;">{{ $dropoffStop }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;">Biển số & Loại xe:</td>
                                        <td style="color: #0f172a; font-weight: 800; font-family: monospace;">{{ $busPlate }} ({{ $busType }})</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;">Bác tài phụ trách:</td>
                                        <td style="color: #0f172a; font-weight: 700;">{{ $driverName }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;">Tổng tiền vé:</td>
                                        <td style="color: #0f172a; font-weight: 800;">{{ $totalAmount }}</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #64748b; font-weight: 600;">Số tiền đã thanh toán:</td>
                                        <td style="color: #047857; font-weight: 800;">{{ $paidAmount }}</td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- CALL TO ACTION: OPEN TICKET -->
                    <tr>
                        <td style="padding: 0 32px 28px 32px; text-align: center;">
                            <a href="{{ $ticketUrl }}" style="display: inline-block; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 900; padding: 14px 32px; border-radius: 14px; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                                📲 Mở Thẻ Lên Xe & Quét Mã QR Ngay
                            </a>
                            <p style="margin: 12px 0 0 0; font-size: 12px; color: #64748b;">
                                Hệ thống AI nhaxekientruc sẽ tự động gửi thông báo nhắc lịch và vị trí xe chạy trước 45 phút khởi hành.
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
