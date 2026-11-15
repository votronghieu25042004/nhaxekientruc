import sqlite3
import random
import sys
from datetime import datetime, timedelta

sys.stdout.reconfigure(encoding='utf-8')

conn = sqlite3.connect('database/database.sqlite')
cur = conn.cursor()

print("===> BẮT ĐẦU CẬP NHẬT DỮ LIỆU THỰC TẾ 100% CHO BUSHUB <===")

# 1. CẬP NHẬT CÁC TUYẾN ĐƯỜNG VÀ THỜI GIAN/GIÁ VÉ CHUẨN XÁC
# Đà Nẵng - Huế: 100 km, 2.8 giờ (150.000đ cho cả 2 chiều!)
routes_data = [
    (1, "Đà Nẵng ⇄ Thừa Thiên Huế", "Đà Nẵng", "Thừa Thiên Huế", 100, 2.8),
    (2, "Thừa Thiên Huế ⇄ Đà Nẵng", "Thừa Thiên Huế", "Đà Nẵng", 100, 2.8),
    (3, "Đà Nẵng ⇄ Quảng Trị (Đông Hà)", "Đà Nẵng", "Quảng Trị", 165, 3.5),
    (4, "Quảng Trị (Đông Hà) ⇄ Đà Nẵng", "Quảng Trị", "Đà Nẵng", 165, 3.5),
    (5, "Đà Nẵng ⇄ Quảng Bình (Đồng Hới)", "Đà Nẵng", "Quảng Bình", 270, 5.0),
    (6, "Quảng Bình (Đồng Hới) ⇄ Đà Nẵng", "Quảng Bình", "Đà Nẵng", 270, 5.0),
    (7, "Đà Nẵng ⇄ Quy Nhơn (Bình Định)", "Đà Nẵng", "Quy Nhơn", 315, 5.5),
    (8, "Quy Nhơn (Bình Định) ⇄ Đà Nẵng", "Quy Nhơn", "Đà Nẵng", 315, 5.5),
    (9, "Đà Nẵng ⇄ Buôn Ma Thuột (Đắk Lắk)", "Đà Nẵng", "Buôn Ma Thuột", 480, 9.0),
    (10, "Buôn Ma Thuột (Đắk Lắk) ⇄ Đà Nẵng", "Buôn Ma Thuột", "Đà Nẵng", 480, 9.0),
]

for r_id, name, orig, dest, dist, est_h in routes_data:
    cur.execute("""
        UPDATE routes 
        SET name = ?, origin = ?, destination = ?, distance_km = ?, estimated_hours = ?, updated_at = '2026-10-28 00:00:00'
        WHERE id = ?
    """, (name, orig, dest, dist, est_h, r_id))

print("-> Đã cập nhật 10 tuyến đường chuẩn (Đà Nẵng ⇄ Huế: 100km, 2.8h, giá 150k cho cả 2 chiều).")

# 2. KHÁCH HÀNG CRM MẪU (40 KHÁCH)
customers = [
    ("Võ Trọng Hiếu", "0905123456", "hieu@gmail.com"),
    ("Nguyễn Văn An", "0905667788", "khachhang@gmail.com"),
    ("Nguyễn Thị Thu Hà", "0912345678", "thuha.nguyen@gmail.com"),
    ("Trần Văn Mạnh", "0913456789", "manh.tran@gmail.com"),
    ("Lê Thị Mỹ Linh", "0914567890", "mylinh.le@gmail.com"),
    ("Phạm Hoàng Long", "0915678901", "hoanglong.pham@gmail.com"),
    ("Đỗ Thu Hương", "0905555666", "huong.dothu@gmail.com"),
    ("Vũ Thanh Tùng", "0905666777", "tung.vuthanh@gmail.com"),
    ("Bùi Mai Phương", "0905777888", "phuong.buimai@gmail.com"),
    ("Hoàng Đức Anh", "0905888999", "ducanh.hoang@gmail.com"),
    ("Ngô Bảo Châu", "0905999000", "chao.ngobao@gmail.com"),
    ("Dương Quốc Toàn", "0905000111", "toan.duong@gmail.com"),
    ("Lý Thùy Trang", "0905111222", "trang.lythuy@gmail.com"),
    ("Phan Hữu Thắng", "0905222333", "thang.phanhuu@gmail.com"),
    ("Đinh Trọng Hưng", "0905333444", "hung.dinhtrong@gmail.com"),
    ("Trịnh Thị Mai", "0905444555", "mai.trinhthi@gmail.com"),
    ("Lâm Hoài An", "0905555111", "an.lamhoai@gmail.com"),
    ("Tạ Đình Phong", "0905666222", "phong.tadinh@gmail.com"),
    ("Đoàn Bích Ngọc", "0905777333", "ngoc.doanbich@gmail.com"),
    ("Hà Quang Vinh", "0905888444", "vinh.haquang@gmail.com"),
    ("Cao Nhật Huy", "0905999555", "huy.caonhat@gmail.com"),
    ("Trương Cẩm Tú", "0905000666", "tu.truongcam@gmail.com"),
    ("Lương Khánh Huyền", "0905111777", "huyen.luong@gmail.com"),
    ("Huỳnh Minh Trí", "0905222888", "tri.huynhminh@gmail.com"),
    ("Tô Văn Hùng", "0905333999", "hung.tovan@gmail.com"),
    ("Quách Ánh Tuyết", "0905444112", "tuyet.quach@gmail.com"),
    ("Bạch Hải Nam", "0905555223", "nam.bachhai@gmail.com"),
    ("Khổng Minh Tuấn", "0905666334", "tuan.khongminh@gmail.com"),
    ("Thái Thùy Dương", "0905777445", "duong.thaithuy@gmail.com"),
    ("Phùng Thế Vinh", "0905888556", "vinh.phungthe@gmail.com"),
    ("Lưu Gia Bảo", "0905999667", "bao.luugia@gmail.com"),
    ("Chu Thị Hương", "0905000778", "huong.chuthi@gmail.com"),
    ("Giang Quốc Đạt", "0905111889", "dat.giangquoc@gmail.com"),
    ("Nghiêm Văn Long", "0905222990", "long.nghiem@gmail.com"),
    ("Ân Gia Hân", "0905333001", "han.angia@gmail.com"),
    ("Trần Tuấn Kiệt", "0905444002", "kiet.trantuan@gmail.com"),
    ("Lê Thị Kim Ngân", "0905555003", "ngan.lekim@gmail.com"),
    ("Nguyễn Minh Khang", "0905666004", "khang.nguyenminh@gmail.com"),
    ("Phạm Quỳnh Chi", "0905777005", "chi.phamquynh@gmail.com"),
    ("Vũ Hoàng My", "0905888006", "my.vuhoang@gmail.com"),
]

customer_ids = []
for c_name, c_phone, c_email in customers:
    row = cur.execute("SELECT id FROM users WHERE phone = ? OR email = ?", (c_phone, c_email)).fetchone()
    if row:
        u_id = row[0]
        cur.execute("UPDATE users SET name = ?, phone = ?, email = ?, role = 'customer' WHERE id = ?", (c_name, c_phone, c_email, u_id))
    else:
        cur.execute("""
            INSERT INTO users (name, phone, email, role, password, status, created_at, updated_at)
            VALUES (?, ?, ?, 'customer', '$2y$12$wrJY3baCNy5nswFv1BUVuOgGXSi3R7AXMtoC.T6p28NRFDH.zNYdG', 'ACTIVE', '2026-10-01 09:00:00', '2026-10-01 09:00:00')
        """, (c_name, c_phone, c_email))
        u_id = cur.lastrowid
    customer_ids.append((u_id, c_name, c_phone, c_email))

# 3. THIẾT LẬP CÁC CHUYẾN XE KHỨ HỒI LOGIC 100%
# Yêu cầu:
# - Xe gần (Đà Nẵng - Huế): 4 chuyến/ngày (2 chuyến đi, 2 chuyến về). Xe 43B-012.34 & Bác tài 1: Nguyễn Văn Hùng
# - Các xe khác: 2 chuyến/ngày (1 chuyến đi, 1 chuyến về). Bác tài & xe đi tới nơi rồi chạy về lại Đà Nẵng!
#   Đặc biệt: Bác tài 7 (Bùi Tiến Dũng) xe 43B-067.89: Sáng đi Quy Nhơn (08:30 -> 14:15), Chiều về Đà Nẵng (16:00 -> 21:45)!

# Danh sách lịch trình mỗi ngày:
# (bus_id, driver_id, conductor_id, route_id, dep_time, arr_time, base_price, is_return, trip_suffix)
daily_schedule_templates = [
    # 1. XE ĐÀ NẴNG ⇄ HUẾ (4 CHUYẾN/NGÀY - TUYẾN GẦN)
    (1, 1, 1, 1, "06:30:00", "09:15:00", 150000, False, "01A"), # Đà Nẵng -> Huế (Sáng)
    (1, 1, 1, 2, "10:15:00", "13:00:00", 150000, True,  "01B"), # Huế -> Đà Nẵng (Trưa)
    (1, 1, 1, 1, "14:00:00", "16:45:00", 150000, False, "02A"), # Đà Nẵng -> Huế (Chiều)
    (1, 1, 1, 2, "17:45:00", "20:30:00", 150000, True,  "02B"), # Huế -> Đà Nẵng (Tối)

    # 2. XE ĐÀ NẴNG ⇄ QUẢNG TRỊ (Bus 2, Bác tài 2: Trần Đình Trọng)
    (2, 2, 2, 3, "07:00:00", "10:30:00", 200000, False, "01A"), # Đà Nẵng -> Quảng Trị
    (2, 2, 2, 4, "13:30:00", "17:00:00", 200000, True,  "01B"), # Quảng Trị -> Đà Nẵng

    # 3. XE ĐÀ NẴNG ⇄ QUẢNG TRỊ (Bus 3, Bác tài 3: Lê Hoàng Nam)
    (3, 3, 3, 3, "08:30:00", "12:00:00", 200000, False, "01A"), # Đà Nẵng -> Quảng Trị
    (3, 3, 3, 4, "15:00:00", "18:30:00", 200000, True,  "01B"), # Quảng Trị -> Đà Nẵng

    # 4. XE ĐÀ NẴNG ⇄ QUẢNG BÌNH (Bus 4, Bác tài 4: Phạm Quốc Bảo)
    (4, 4, 4, 5, "06:30:00", "11:30:00", 280000, False, "01A"), # Đà Nẵng -> Quảng Bình
    (4, 4, 4, 6, "13:30:00", "18:30:00", 280000, True,  "01B"), # Quảng Bình -> Đà Nẵng

    # 5. XE ĐÀ NẴNG ⇄ QUẢNG BÌNH (Bus 5, Bác tài 5: Hoàng Minh Tuấn)
    (5, 5, 5, 5, "08:00:00", "13:00:00", 280000, False, "01A"), # Đà Nẵng -> Quảng Bình
    (5, 5, 5, 6, "15:00:00", "20:00:00", 280000, True,  "01B"), # Quảng Bình -> Đà Nẵng

    # 6. XE ĐÀ NẴNG ⇄ QUY NHƠN (Bus 6, Bác tài 6: Đặng Văn Lâm)
    (6, 6, 6, 7, "06:00:00", "11:30:00", 360000, False, "01A"), # Đà Nẵng -> Quy Nhơn
    (6, 6, 6, 8, "13:30:00", "19:00:00", 360000, True,  "01B"), # Quy Nhơn -> Đà Nẵng

    # 7. XE ĐÀ NẴNG ⇄ QUY NHƠN (Bus 7, Bác tài 7: Bùi Tiến Dũng) -> ĐÚNG LOGIC ĐI & VỀ
    (7, 7, 7, 7, "08:30:00", "14:15:00", 360000, False, "01A"), # Đà Nẵng -> Quy Nhơn (Sáng)
    (7, 7, 7, 8, "16:00:00", "21:45:00", 360000, True,  "01B"), # Quy Nhơn -> Đà Nẵng (Chiều Tối)

    # 8. XE ĐÀ NẴNG ⇄ QUY NHƠN (Bus 8, Bác tài 8: Hồ Tấn Tài)
    (8, 8, 8, 7, "09:30:00", "15:15:00", 360000, False, "01A"), # Đà Nẵng -> Quy Nhơn
    (8, 8, 8, 8, "17:00:00", "22:45:00", 360000, True,  "01B"), # Quy Nhơn -> Đà Nẵng

    # 9. XE ĐÀ NẴNG ⇄ BUÔN MA THUỘT (Bus 9, Bác tài 9: Vũ Văn Thanh)
    (9, 9, 9, 9, "07:30:00", "16:30:00", 420000, False, "01A"), # Đà Nẵng -> Buôn Ma Thuột
    (9, 9, 9, 10, "19:00:00", "04:00:00", 420000, True,  "01B"), # Buôn Ma Thuột -> Đà Nẵng (Đêm)

    # 10. XE ĐÀ NẴNG ⇄ BUÔN MA THUỘT (Bus 10, Bác tài 10: Trần Văn Kiên)
    (10, 10, 10, 9, "08:30:00", "17:30:00", 420000, False, "01A"), # Đà Nẵng -> Buôn Ma Thuột
    (10, 10, 10, 10, "20:00:00", "05:00:00", 420000, True,  "01B"), # Buôn Ma Thuột -> Đà Nẵng (Đêm)
]

# Xóa các bảng dữ liệu liên quan để nạp mới toàn diện
cur.execute("DELETE FROM booking_seats")
cur.execute("DELETE FROM bookings")
cur.execute("DELETE FROM trip_expenses")
cur.execute("DELETE FROM trip_closings")
cur.execute("DELETE FROM trips")

print("-> Đã dọn dẹp các bảng chuyến xe và vé cũ.")

# Tạo 6 ngày liên tiếp: 3 ngày quá khứ (25, 26, 27) + 28 (Hôm nay) + 29 (Ngày mai) + 30 (Ngày kia)
dates_config = [
    ("2026-10-25", "PAST"),
    ("2026-10-26", "PAST"),
    ("2026-10-27", "PAST"),
    ("2026-10-28", "TODAY"),
    ("2026-10-29", "FUTURE"),
    ("2026-10-30", "FUTURE"),
]

trip_counter = 1
booking_counter = 1
seat_counter = 1

seat_names = [f"A{i:02d}" for i in range(1, 18)] + [f"B{i:02d}" for i in range(1, 18)] # 34 ghế

all_seeded_trips = []

# Danh sách voucher
vouchers_list = [
    ("CHAOBANMOI", 0.10, 50000), # 10%, max 50k
    ("BUSHUB20", None, 20000),    # Giảm thẳng 20k
    ("MIENTRUNG15", 0.15, 60000),# 15%, max 60k
]

for date_str, day_type in dates_config:
    day_num = date_str.split("-")[2]
    
    for (b_id, d_id, c_id, r_id, dep_t, arr_t, base_pr, is_ret, suffix) in daily_schedule_templates:
        t_code = f"BH-{day_num}{b_id:02d}-{suffix}"
        dep_datetime = f"{date_str} {dep_t}"
        
        # Xử lý nếu thời gian đến sang ngày hôm sau (chuyến chạy đêm)
        if dep_t > arr_t:
            arr_date = (datetime.strptime(date_str, "%Y-%m-%d") + timedelta(days=1)).strftime("%Y-%m-%d")
        else:
            arr_date = date_str
        arr_datetime = f"{arr_date} {arr_t}"

        # Xác định trạng thái chuyến xe
        if day_type == "PAST":
            status = "COMPLETED"
            dep_at = dep_datetime
            arr_at = arr_datetime
        elif day_type == "TODAY":
            # Ngày 28/10:
            # Chuyến sáng < 12h: COMPLETED
            # Chuyến 12h - 18h: IN_TRANSIT (Đang chạy trên đường) hoặc BOARDING
            # Chuyến tối >= 18h: OPEN_FOR_SALE (Sắp chạy / Mở bán)
            dep_hour = int(dep_t.split(":")[0])
            if dep_hour < 12:
                status = "COMPLETED"
                dep_at = dep_datetime
                arr_at = arr_datetime
            elif dep_hour < 17:
                status = "IN_TRANSIT"
                dep_at = dep_datetime
                arr_at = None
            else:
                status = "OPEN_FOR_SALE"
                dep_at = None
                arr_at = None
        else: # FUTURE (29, 30)
            status = "OPEN_FOR_SALE"
            dep_at = None
            arr_at = None

        cur.execute("""
            INSERT INTO trips (
                id, trip_code, route_id, bus_id, driver_id, conductor_id,
                departure_time, arrival_time, base_price, floor1_price, floor2_price,
                floor1_seat_type, floor2_seat_type, status, current_stop_order,
                departed_at, completed_at, closed_at, closed_by, current_speed_kmh,
                delay_minutes, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Giường Nằm VIP', 'Giường Nằm Tiêu Chuẩn', ?, 1, ?, ?, ?, ?, ?, 0, '2026-10-24 08:00:00', '2026-10-24 08:00:00')
        """, (
            trip_counter, t_code, r_id, b_id, d_id, c_id,
            dep_datetime, arr_datetime, base_pr, base_pr, base_pr - 10000,
            status,
            dep_at,
            arr_at if status == "COMPLETED" else None,
            arr_at if status == "COMPLETED" else None,
            "Admin Điều Hành" if status == "COMPLETED" else None,
            62 if status == "IN_TRANSIT" else 0
        ))

        all_seeded_trips.append((trip_counter, t_code, r_id, b_id, d_id, c_id, dep_datetime, status, base_pr, is_ret))

        # TẠO VÉ (BOOKINGS) CHO CHUYẾN XE:
        # YÊU CẦU: "xe nào cũng có khách full hết 1 nửa xe nhé", "đầy đủ có tình huống ví dụ đặt trên web có khách trả full tiền, có khách thanh toán trước 30%, có khách xài mã giảm giá, có khách đặt vé tại quầy hoặc tại xe, có khách hủy vé"
        # Chọn từ 18 đến 24 ghế (~55% - 70% tổng 34 ghế)
        num_booked = random.randint(18, 24)
        available_seats = list(seat_names)
        random.shuffle(available_seats)
        chosen_seats = available_seats[:num_booked]

        for s_i, seat_num in enumerate(chosen_seats):
            cust = customer_ids[(trip_counter * 5 + s_i) % len(customer_ids)]
            u_id, c_name, c_phone, c_email = cust
            is_floor2 = seat_num.startswith('B')
            seat_price = base_pr - 10000 if is_floor2 else base_pr

            # Phân bổ nguồn đặt vé:
            # 60% WEB, 25% POS (Tại quầy), 15% ROADSIDE_ON_BUS (Tại xe / Dọc đường)
            if s_i % 10 in [0, 1, 2, 3, 4, 5]:
                b_source = "WEB"
            elif s_i % 10 in [6, 7]:
                b_source = "POS"
            else:
                b_source = "ROADSIDE_ON_BUS"

            # Phân bổ Voucher:
            voucher_code = None
            discount_amt = 0
            if b_source == "WEB" and s_i % 4 == 0:
                v_item = vouchers_list[s_i % len(vouchers_list)]
                voucher_code = v_item[0]
                if v_item[1]:
                    discount_amt = min(int(seat_price * v_item[1]), v_item[2])
                else:
                    discount_amt = v_item[2]

            final_total = max(10000, seat_price - discount_amt)

            # Phân bổ tình huống thanh toán:
            # 1. HỦY VÉ (REFUNDED) ~ 5% (1 ghế mỗi chuyến nếu s_i == 0)
            # 2. CỌC 30% (PARTIAL_PAID) ~ 25%
            # 3. THANH TOÁN ĐỦ 100% (PAID) ~ 60%
            # 4. CHƯA THANH TOÁN (UNPAID - CASH trên xe) ~ 10%
            if s_i == 0 and status in ["COMPLETED", "OPEN_FOR_SALE"]:
                payment_status = "REFUNDED"
                paid_amount = final_total
                payment_method = "MOMO"
                checkin_status = "NO_SHOW"
                cancel_reason = "Khách bận việc đột xuất xin hủy trước 24h (Đã hoàn tiền 100%)"
                notes = "Đã hoàn trả ví MoMo"
            elif s_i % 4 == 1:
                # CỌC 30%
                payment_status = "PARTIAL_PAID"
                paid_amount = int(final_total * 0.3)
                payment_method = "BANKING" if b_source == "WEB" else "CASH"
                cancel_reason = None
                notes = f"Đã cọc 30% ({paid_amount:,}đ). Cần thu còn lại {final_total - paid_amount:,}đ khi lên xe."
                if status == "COMPLETED":
                    checkin_status = "CHECKED_IN" # Đã lên xe và lơ xe đã thu đủ tiền
                elif status == "IN_TRANSIT":
                    checkin_status = "CHECKED_IN" if s_i % 2 == 0 else "PENDING"
                else:
                    checkin_status = "PENDING"
            elif s_i % 6 == 2 and b_source == "ROADSIDE_ON_BUS":
                # TẠI XE CHƯA THANH TOÁN TRƯỚC
                payment_status = "PAID" if status == "COMPLETED" else "UNPAID"
                paid_amount = final_total if status == "COMPLETED" else 0
                payment_method = "CASH"
                cancel_reason = None
                notes = "Khách đón tại ngã ba dọc đường. Trả tiền mặt trực tiếp cho lơ xe."
                checkin_status = "CHECKED_IN" if status == "COMPLETED" else "PENDING"
            else:
                # FULL 100%
                payment_status = "PAID"
                paid_amount = final_total
                if b_source == "WEB":
                    payment_method = random.choice(["VNPAY", "MOMO", "VIETQR", "ONLINE_CARD"])
                elif b_source == "POS":
                    payment_method = random.choice(["CASH", "VIETQR", "POS_CARD"])
                else:
                    payment_method = "CASH"
                cancel_reason = None
                notes = "Thanh toán thành công 100%."
                if status == "COMPLETED":
                    checkin_status = "CHECKED_IN" if s_i != 3 else "NO_SHOW"
                elif status == "IN_TRANSIT":
                    checkin_status = "CHECKED_IN" if s_i % 3 != 0 else "PENDING"
                else:
                    checkin_status = "PENDING"

            b_code = f"BH-{t_code}-{s_i+1:02d}"
            qr_token = f"QR_{b_code}"
            
            # Thời điểm đặt vé
            hours_ago = random.randint(4, 72)
            booking_dt = (datetime.strptime(dep_datetime, "%Y-%m-%d %H:%M:%S") - timedelta(hours=hours_ago)).strftime("%Y-%m-%d %H:%M:%S")
            checkin_dt = dep_datetime if checkin_status == "CHECKED_IN" else None

            cur.execute("""
                INSERT INTO bookings (
                    id, booking_code, user_id, trip_id, booking_source,
                    customer_name, customer_phone, customer_email,
                    pickup_stop_id, dropoff_stop_id, pickup_order, dropoff_order,
                    total_seats, total_amount, paid_amount, payment_status, payment_method,
                    checkin_status, checkin_method, checked_in_at, qr_token, notes,
                    voucher_code, discount_amount, cancel_reason, trip_direction,
                    audit_status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 3, 1, 5, 1, ?, ?, ?, ?, ?, 'QR_SCAN', ?, ?, ?, ?, ?, ?, ?, 'AUDITED', ?, ?)
            """, (
                booking_counter, b_code, u_id, trip_counter, b_source,
                c_name, c_phone, c_email,
                final_total, paid_amount, payment_status, payment_method,
                checkin_status, checkin_dt, qr_token, notes,
                voucher_code, discount_amt, cancel_reason,
                "Chuyến Về" if is_ret else "Chuyến Đi",
                booking_dt, booking_dt
            ))

            cur.execute("""
                INSERT INTO booking_seats (
                    id, booking_id, trip_id, seat_number, floor, pickup_order, dropoff_order, price, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, 1, 5, ?, ?, ?)
            """, (
                seat_counter, booking_counter, trip_counter, seat_num, 2 if is_floor2 else 1, seat_price,
                booking_dt, booking_dt
            ))

            booking_counter += 1
            seat_counter += 1

        # Chốt ca tài chính cho chuyến đã hoàn thành
        if status == "COMPLETED":
            fuel_cost = random.randint(350000, 600000)
            toll_cost = random.randint(120000, 250000)
            tot_exp = fuel_cost + toll_cost
            cash_submitted = random.randint(800000, 2200000)
            cur.execute("""
                INSERT INTO trip_closings (
                    trip_id, total_revenue, total_expenses, net_profit,
                    expected_cash_from_conductor, actual_cash_submitted, cash_discrepancy,
                    closed_by, notes, trip_direction, audit_status, audited_by, audited_at,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 0, 'Nguyễn Văn Phúc (Lơ Xe Trưởng)', 'Chốt ca đầy đủ và an toàn', 'Chuyến Đi', 'AUDITED', 'Admin Kế Toán', ?, ?, ?)
            """, (
                trip_counter, cash_submitted + 2500000, tot_exp, (cash_submitted + 2500000) - tot_exp,
                cash_submitted, cash_submitted, dep_datetime, dep_datetime, dep_datetime
            ))

        trip_counter += 1

print(f"-> Đã tạo thành công {trip_counter - 1} chuyến xe và {booking_counter - 1} vé đặt thực tế.")

# 4. TẠO 50 BẢN GHI AUDIT LOGS CHUẨN XÁC ĐỂ TRÌNH BÀY
cur.execute("DELETE FROM audit_logs")

audit_events = [
    ("LOGIN_ADMIN", "Auth", "1", "Quản trị viên Admin đăng nhập thành công từ IP 118.69.182.44"),
    ("CREATE_BOOKING", "Booking", "BH-2801-01A-01", "Nhân viên Nguyễn Văn A xuất vé quầy #BH-2801-01A-01 cho khách Võ Trọng Hiếu (Ghế A01, Đà Nẵng ➔ Huế)"),
    ("COLLECT_CASH", "Booking", "BH-2801-01A-02", "Lơ xe Nguyễn Văn Phúc thu đủ tiền mặt 105.000đ (còn thiếu 70%) từ khách cọc Nguyễn Thị Thu Hà"),
    ("SCAN_QR_CHECKIN", "Booking", "BH-2801-01A-03", "Lơ xe quét mã QR xác thực thành công cho khách Trần Văn Mạnh tại Trạm Bến Xe Trung Tâm Đà Nẵng"),
    ("CHANGE_SEAT", "Booking", "BH-2801-01A-04", "Lơ xe đổi ghế khách Lê Thị Mỹ Linh từ A04 sang B02 theo yêu cầu hỗ trợ người cao tuổi"),
    ("APPLY_VOUCHER", "Booking", "BH-2807-01A-05", "Khách hàng áp dụng thành công mã voucher CHAOBANMOI giảm 10% khi đặt trên Web"),
    ("START_TRIP", "Trip", "BH-2807-01A", "Bác tài Bùi Tiến Dũng bấm Bắt Đầu Xuất Bến chuyến Đà Nẵng ➔ Quy Nhơn (Xe 43B-067.89)"),
    ("TRIP_DELAY_ALERT", "Trip", "BH-2807-01A", "Hệ thống ghi nhận chuyến xe đến trạm chậm 10 phút do thời tiết mưa lớn tại Đèo Cù Mông"),
    ("CLOSE_SHIFT", "TripClosing", "BH-2801-01A", "Lơ xe Nguyễn Văn Phúc nộp 1.450.000đ tiền mặt cho Kế toán đối soát sau chuyến đi"),
    ("AUDIT_APPROVE", "TripClosing", "BH-2801-01A", "Kế toán trưởng phê duyệt hồ sơ đối soát doanh thu chuyến xe hoàn tất"),
    ("REFUND_TICKET", "Booking", "BH-2807-01A-01", "Hệ thống tự động hoàn tiền 360.000đ về ví MoMo do khách hủy vé trước 24 giờ"),
    ("UPDATE_PRICING", "PricingRule", "PR-HUE-01", "Admin cập nhật biểu phí vé xe tuyến Đà Nẵng ⇄ Huế cố định 150.000đ"),
    ("MAINTENANCE_LOG", "Bus", "43B-012.34", "Ghi nhận bảo dưỡng định kỳ thay dầu nhớt và kiểm tra má phanh xe 43B-012.34"),
    ("FUEL_FILL", "Bus", "43B-067.89", "Bác tài Bùi Tiến Dũng cập nhật đổ 120 lít dầu Diesel tại Petrolimex Hòa Cầm (2.450.000đ)"),
    ("DISPATCH_NOTIFY", "Trip", "BH-2807-02B", "Điều độ trung tâm gửi thông báo nhắc nhở chuẩn bị xuất bến cho Lơ xe & Bác tài ca 16:00"),
]

log_date_base = datetime(2026, 10, 26, 7, 30)
for idx in range(50):
    ev = audit_events[idx % len(audit_events)]
    action, entity_t, entity_i, desc = ev
    log_time = (log_date_base + timedelta(hours=idx * 1.3)).strftime("%Y-%m-%d %H:%M:%S")
    user_name = "Nguyễn Văn Phúc (Lơ Xe)" if "Lơ xe" in desc else ("Bùi Tiến Dũng (Bác Tài)" if "Bác tài" in desc else "Admin Quản Trị")
    u_id = 1 if "Admin" in user_name else (7 if "Bác Tài" in user_name else 3)

    cur.execute("""
        INSERT INTO audit_logs (
            id, user_id, user_name, action, entity_type, entity_id, description,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    """, (idx + 1, u_id, user_name, action, entity_t, entity_i, desc, log_time, log_time))

print(f"-> Đã tạo 50 bản ghi Audit Logs chi tiết phục vụ trình bày.")

conn.commit()
conn.close()
print("===> HOÀN TẤT SEEDING MASTER REALISTIC THÀNH CÔNG 100%! <===")
