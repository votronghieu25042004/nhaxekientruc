<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Models\Trip;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Lấy danh sách thông báo theo phân quyền người dùng (Khách hàng, Admin, Lơ xe, Tài xế)
     */
    public function getNotifications(?Request $request = null)
    {
        $user = Auth::user();
        $role = $user ? $user->role : 'customer';

        $sessionReadIds = session('read_notifications', []);
        $notifications = [];

        if ($role === 'admin') {
            // 1. Thông báo cho Admin
            $pendingRefunds = Booking::where('refund_status', 'PENDING')->orderBy('created_at', 'desc')->take(3)->get();
            foreach ($pendingRefunds as $b) {
                $notifications[] = [
                    'id' => 'refund_' . $b->id,
                    'type' => 'refund',
                    'icon' => '🚫',
                    'title' => 'Yêu cầu hoàn tiền vé #' . $b->booking_code,
                    'content' => "Khách hàng {$b->customer_name} yêu cầu hoàn " . number_format($b->refund_amount ?: $b->paid_amount) . "đ. Cần xét duyệt!",
                    'detail' => "Khách hàng: {$b->customer_name}\nSố điện thoại: {$b->customer_phone}\nMã vé: #{$b->booking_code}\nSố tiền đề nghị hoàn: " . number_format($b->refund_amount ?: $b->paid_amount) . " VNĐ.\nLý do: " . ($b->refund_reason ?: 'Khách hàng đổi lịch trình công tác') . ".\nVui lòng vào mục Quản lý hoàn tiền để rà soát và phê duyệt chuyển khoản cho khách.",
                    'time' => $b->created_at ? $b->created_at->diffForHumans() : 'Vừa xong',
                    'link' => '/admin/refunds',
                    'action_label' => 'Xem Xét Duyệt Hoàn Tiền',
                    'is_read' => in_array('refund_' . $b->id, $sessionReadIds),
                ];
            }

            $recentBookings = Booking::with('trip.route')->orderBy('created_at', 'desc')->take(4)->get();
            foreach ($recentBookings as $b) {
                $statusText = $b->payment_status === 'PAID' ? 'Đã thanh toán 100%' : 'Đã đặt cọc 30%';
                $routeName = $b->trip?->route?->name ?? 'Tuyến liên tỉnh';
                $notifications[] = [
                    'id' => 'booking_' . $b->id,
                    'type' => 'booking',
                    'icon' => '🎫',
                    'title' => 'Đơn đặt vé mới #' . $b->booking_code,
                    'content' => "{$b->customer_name} ({$b->customer_phone}) vừa đặt {$routeName} - {$statusText}.",
                    'detail' => "Khách hàng {$b->customer_name} ({$b->customer_phone}) vừa hoàn tất đặt chỗ chuyến {$routeName}.\nTrạng thái: {$statusText}.\nTổng tiền: " . number_format($b->total_amount) . " VNĐ (Đã thu: " . number_format($b->paid_amount) . " VNĐ).\nHóa đơn và mã vé điện tử đã được hệ thống tự động xuất lưu trữ tại thư mục PDF.",
                    'time' => $b->created_at ? $b->created_at->diffForHumans() : 'Gần đây',
                    'link' => '/admin/trips',
                    'action_label' => 'Xem Quản Lý Vé',
                    'is_read' => in_array('booking_' . $b->id, $sessionReadIds),
                ];
            }
        } elseif ($role === 'driver') {
            // 2. Thông báo cho Tài xế
            $todayTrips = Trip::with('route')->whereDate('departure_time', Carbon::today())->take(2)->get();
            foreach ($todayTrips as $t) {
                $depTime = $t->departure_time ? Carbon::parse($t->departure_time)->format('H:i') : '07:00';
                $notifications[] = [
                    'id' => 'driver_trip_' . $t->id,
                    'type' => 'trip',
                    'icon' => '🚍',
                    'title' => 'Điều phối chuyến xe #' . $t->trip_code,
                    'content' => "Lộ trình {$t->route?->name}. Xuất bến lúc {$depTime}. Hãy kiểm tra an toàn phương tiện!",
                    'detail' => "Bác tài đã được phân công phụ trách chuyến xe #{$t->trip_code}.\nTuyến: {$t->route?->name}.\nGiờ xuất bến: {$depTime} hôm nay.\nYêu cầu chuẩn bị:\n• Kiểm tra áp suất lốp, dầu nhớt và hệ thống phanh an toàn.\n• Khởi động camera hành trình và thiết bị định vị GPS trước khi rời bến 15 phút.",
                    'time' => 'Hôm nay',
                    'link' => '/driver',
                    'action_label' => 'Mở Bàn Điều Khiển Lái Xe',
                    'is_read' => in_array('driver_trip_' . $t->id, $sessionReadIds),
                ];
            }

            $notifications[] = [
                'id' => 'driver_safety',
                'type' => 'safety',
                'icon' => '⚠️',
                'title' => 'Quy định an toàn & Nghỉ ngơi',
                'content' => 'Quy định pháp luật: Chú ý đổi ca lái xe sau 4 tiếng liên tục để đảm bảo an toàn tuyệt đối.',
                'detail' => "Theo quy chuẩn an toàn giao thông đường bộ và quy chế vận tải Nhà xe Kiến Trúc:\n• Nghiêm cấm tài xế lái xe liên tục quá 4 giờ.\n• Mỗi tài xế phải nghỉ ngơi tối thiểu 15 phút sau mỗi chặng dài.\n• Giữ tốc độ an toàn qua các đoạn đèo dốc và khu dân cư.",
                'time' => 'Hệ thống',
                'link' => '/driver',
                'action_label' => 'Xem Lịch Trình',
                'is_read' => in_array('driver_safety', $sessionReadIds),
            ];
        } elseif ($role === 'conductor' || $role === 'staff') {
            // 3. Thông báo cho Lơ xe / Phụ xe
            $activeTrip = Trip::with('route')->whereIn('status', ['BOARDING', 'READY', 'IN_TRANSIT'])->first();
            if ($activeTrip) {
                $notifications[] = [
                    'id' => 'conductor_pax_' . $activeTrip->id,
                    'type' => 'passenger',
                    'icon' => '🚶',
                    'title' => 'Danh sách đón trả khách #' . $activeTrip->trip_code,
                    'content' => "Chuyến {$activeTrip->route?->name} đang đón khách. Kiểm tra vé điện tử và số ghế trước khi xuất bến.",
                    'detail' => "Chuyến xe #{$activeTrip->trip_code} ({$activeTrip->route?->name}) đang trong quá trình đón khách.\nNhiệm vụ phụ xe:\n• Dùng camera quét mã QR trên vé điện tử của từng khách.\n• Hướng dẫn hành khách lên đúng số giường/ghế quy định.\n• Kiểm tra thu phần tiền 70% còn lại đối với các đơn vé chọn hình thức đặt cọc 30%.",
                    'time' => 'Đang chạy',
                    'link' => '/conductor',
                    'action_label' => 'Mở Danh Sách Khách',
                    'is_read' => in_array('conductor_pax_' . $activeTrip->id, $sessionReadIds),
                ];
            }

            $notifications[] = [
                'id' => 'conductor_luggage',
                'type' => 'luggage',
                'icon' => '🏷️',
                'title' => 'Kiểm đếm hành lý dán tem',
                'content' => 'Đảm bảo dán tem cuống thẻ hành lý cho tất cả vali dưới gầm xe trước khi xe lăn bánh.',
                'detail' => "Quy trình kiểm soát hành lý ký gửi:\n• Mọi kiện hành lý cất trong hầm xe bắt buộc phải dán tem đôi (1 tem dán vào kiện đồ, 1 cuống giao cho hành khách giữ).\n• Đối với xe máy ký gửi, kiểm tra rút cạn xăng và bọc bảo vệ gương đèn trước khi xếp vào khoang.",
                'time' => 'Quy trình',
                'link' => '/conductor',
                'action_label' => 'Kiểm Tra Chuyến Xe',
                'is_read' => in_array('conductor_luggage', $sessionReadIds),
            ];
        } else {
            // 4. Thông báo cho Khách hàng
            if ($user) {
                // Kiểm tra xem khách có vé xe nào không
                $userBookings = Booking::with('trip.route')->where('user_id', $user->id)->orWhere('customer_phone', $user->phone)->orderBy('created_at', 'desc')->take(3)->get();
                foreach ($userBookings as $b) {
                    $depText = $b->trip?->departure_time ? Carbon::parse($b->trip->departure_time)->format('H:i d/m') : 'Sắp tới';
                    $notifications[] = [
                        'id' => 'cust_b_' . $b->id,
                        'type' => 'ticket',
                        'icon' => '🎫',
                        'title' => 'Vé xe điện tử #' . $b->booking_code,
                        'content' => "Chuyến {$b->trip?->route?->name} khởi hành lúc {$depText}. Bấm để xem mã QR và vé PDF.",
                        'detail' => "Mã vé điện tử: #{$b->booking_code}\nTuyến đường: {$b->trip?->route?->name}\nKhởi hành: {$depText}\nSố ghế đã chọn: " . $b->bookingSeats->pluck('seat_number')->join(', ') . "\n\nVé của quý khách đã được xác thực an toàn trên hệ thống. Quý khách vui lòng có mặt tại điểm đón trước 20 phút và mở mã QR trên điện thoại để bác tài/lơ xe quét mã nhanh chóng.",
                        'time' => $b->created_at ? $b->created_at->diffForHumans() : 'Vừa xong',
                        'link' => '/my-bookings',
                        'action_label' => 'Mở Vé Của Tôi',
                        'is_read' => in_array('cust_b_' . $b->id, $sessionReadIds),
                    ];
                }

                // Kiểm tra phân loại tài khoản: Sinh viên hay Khách hàng phổ thông
                $isStudent = (bool)($user->is_student || str_contains(strtolower($user->email ?? ''), '.edu.vn') || str_contains(strtolower($user->email ?? ''), 'sinhvien'));

                if ($isStudent) {
                    // CHỈ HIỂN THỊ THÔNG BÁO SINH VIÊN KHI TÀI KHOẢN ĐÚNG LÀ SINH VIÊN
                    $notifications[] = [
                        'id' => 'cust_promo_student',
                        'type' => 'promo',
                        'icon' => '🎓',
                        'title' => 'Đặc quyền Sinh viên - Giảm 30%',
                        'content' => 'Nhập ngay mã PRO30 hoặc SINHVIEN khi chọn ghế để được giảm ngay 30% giá vé trên mọi chuyến xe!',
                        'detail' => "Chương trình trợ giá sinh viên độc quyền của Nhà xe Kiến Trúc Đà Nẵng:\n\n• Nhập mã ưu đãi PRO30 hoặc SINHVIEN ở bước đặt vé để được giảm ngay 30% trực tiếp vào giá vé.\n• Áp dụng trọn đời cho sinh viên các trường ĐH/CĐ trên mọi chuyến xe.\n• Khi lên xe, quý khách vui lòng xuất trình thẻ sinh viên hoặc ứng dụng sinh viên số.",
                        'time' => 'Ưu đãi sinh viên',
                        'link' => '/',
                        'action_label' => 'Đặt Vé Trợ Giá Ngay',
                        'is_read' => in_array('cust_promo_student', $sessionReadIds),
                    ];
                } else {
                    // TÀI KHOẢN KHÁCH HÀNG BÌNH THƯỜNG / HỘI VIÊN
                    $tier = $user->loyalty_tier ?? 'MEMBER';
                    $tierName = match($tier) {
                        'DIAMOND' => 'VIP Kim Cương',
                        'GOLD' => 'VIP Vàng',
                        'SILVER' => 'Thân Thiết Bạc',
                        default => 'Hội Viên Thân Thiết',
                    };

                    $notifications[] = [
                        'id' => 'cust_promo_general',
                        'type' => 'promo',
                        'icon' => '⭐',
                        'title' => "Đặc quyền {$tierName} - {$user->name}",
                        'content' => 'Tích lũy điểm thưởng đổi vé miễn phí & trải nghiệm dịch vụ Limousine Cung Điện đẳng cấp!',
                        'detail' => "Kính chào quý khách {$user->name}!\n\n• Hạng thành viên hiện tại của bạn: {$tierName}.\n• Tích điểm thông minh: Mỗi chuyến xe quý khách đi cùng Nhà xe Kiến Trúc sẽ được tích lũy điểm thưởng trực tiếp vào tài khoản để quy đổi vé miễn phí hoặc quà tặng tri ân.\n• Đội xe Limousine đời mới: Đầy đủ tiện nghi đẳng cấp với ghế nằm massage, màn hình giải trí riêng, cổng sạc Type-C siêu nhanh, wifi 5G tốc độ cao và nước suối miễn phí suốt hành trình.",
                        'time' => 'Hội viên',
                        'link' => '/',
                        'action_label' => 'Tìm Chuyến Xe Ngay',
                        'is_read' => in_array('cust_promo_general', $sessionReadIds),
                    ];
                }

                // Chính sách đặt cọc 30% giữ chỗ (chỉ dành cho tài khoản đã đăng nhập)
                $notifications[] = [
                    'id' => 'cust_deposit',
                    'type' => 'info',
                    'icon' => '💵',
                    'title' => 'Chính sách đặt cọc 30% giữ chỗ',
                    'content' => 'Quý khách chỉ cần cọc 30% để khóa giữ chỗ 100%. 70% còn lại thanh toán khi lên xe.',
                    'detail' => "CHÍNH SÁCH ĐẶT CỌC 30% GIỮ CHỖ LINH HOẠT:\n\n1. Khóa giữ chỗ 100%: Quý khách chỉ cần thanh toán trước 30% giá vé qua hình thức quét mã VietQR ngân hàng. Chỗ ngồi được khóa an toàn tuyệt đối trên hệ thống.\n2. Thanh toán phần còn lại khi lên xe: 70% số tiền còn lại quý khách thanh toán trực tiếp cho bác tài hoặc lơ xe bằng tiền mặt hoặc chuyển khoản khi lên xe.\n3. Hủy vé & Hoàn tiền: Quý khách hoàn toàn có thể tự hủy vé trên website trước giờ xe chạy theo quy định hoàn tiền để được nhận lại tiền cọc nhanh chóng.",
                    'time' => 'Hướng dẫn',
                    'link' => '/my-bookings',
                    'action_label' => 'Xem Vé Của Tôi',
                    'is_read' => in_array('cust_deposit', $sessionReadIds),
                ];
            } else {
                // Khách chưa đăng nhập (hoặc vừa đăng xuất) -> Không có thông báo cá nhân
                $notifications = [];
            }
        }

        $unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

        return response()->json([
            'success' => true,
            'role' => $role,
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Đánh dấu thông báo đã đọc
     */
    public function markAsRead(Request $request)
    {
        $id = $request->input('id');
        $all = $request->input('all', false);

        $current = session('read_notifications', []);
        if (!is_array($current)) {
            $current = [];
        }

        if ($all) {
            $ids = $request->input('ids', []);
            $current = array_unique(array_merge($current, $ids));
        } elseif ($id) {
            $current = array_unique(array_merge($current, [$id]));
        }

        session(['read_notifications' => $current]);

        return response()->json([
            'success' => true,
            'read_count' => count($current),
        ]);
    }
}
