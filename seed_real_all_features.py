import sqlite3
import sys
import random
from datetime import datetime, timedelta

sys.stdout.reconfigure(encoding='utf-8')

conn = sqlite3.connect('database/database.sqlite')
cur = conn.cursor()

print("--> Bắt đầu cập nhật cơ sở dữ liệu BusHub hoàn chỉnh 100%...")

# 1. Cập nhật và liên kết 10 Tài xế với 10 Tài khoản User Driver
# Hash bcrypt của mật khẩu '123456'
bcrypt_123456 = "$2y$12$wrJY3baCNy5nswFv1BUVuOgGXSi3R7AXMtoC.T6p28NRFDH.zNYdG"

drivers_info = [
    (1, "Nguyễn Văn Hùng", "0905123456", "taixe1@bushub.vn", "GPLX-43000001", "Hạng E", 12, "2027-11-15", "048092000111"),
    (2, "Trần Đình Trọng", "0905234567", "taixe2@bushub.vn", "GPLX-43000002", "Hạng E", 10, "2027-08-20", "048092000222"),
    (3, "Lê Hoàng Nam", "0905345678", "taixe3@bushub.vn", "GPLX-43000003", "Hạng E", 8, "2028-05-10", "048092000333"),
    (4, "Phạm Quốc Bảo", "0905456789", "taixe4@bushub.vn", "GPLX-43000004", "Hạng E", 9, "2027-12-05", "048092000444"),
    (5, "Hoàng Minh Tuấn", "0905567890", "taixe5@bushub.vn", "GPLX-43000005", "Hạng E", 11, "2028-01-18", "048092000555"),
    (6, "Đặng Văn Lâm", "0905678901", "taixe6@bushub.vn", "GPLX-43000006", "Hạng E", 7, "2028-03-22", "048092000666"),
    (7, "Bùi Tiến Dũng", "0905789012", "driver@bushub.vn", "GPLX-43000007", "Hạng E", 14, "2027-09-30", "048092000777"),
    (8, "Hồ Tấn Tài", "0905890123", "taixe8@bushub.vn", "GPLX-43000008", "Hạng E", 6, "2028-07-14", "048092000888"),
    (9, "Vũ Văn Thanh", "0905901234", "taixe9@bushub.vn", "GPLX-43000009", "Hạng E", 9, "2028-02-28", "048092000999"),
    (10, "Trần Văn Kiên", "0905012345", "taixe10@bushub.vn", "GPLX-43000010", "Hạng E", 5, "2027-10-12", "048092000100"),
]

for d_id, name, phone, email, gplx, g_class, exp, exp_date, cccd in drivers_info:
    # Check if user exists
    user_row = cur.execute("SELECT id FROM users WHERE email = ? OR phone = ?", (email, phone)).fetchone()
    if user_row:
        u_id = user_row[0]
        cur.execute("""
            UPDATE users SET name = ?, phone = ?, email = ?, role = 'driver', password = ? WHERE id = ?
        """, (name + " (Bác Tài)", phone, email, bcrypt_123456, u_id))
    else:
        cur.execute("""
            INSERT INTO users (name, email, phone, role, password, status, created_at, updated_at)
            VALUES (?, ?, ?, 'driver', ?, 'ACTIVE', '2026-10-01 08:00:00', '2026-10-01 08:00:00')
        """, (name + " (Bác Tài)", email, phone, bcrypt_123456))
        u_id = cur.lastrowid

    # Check driver record
    d_row = cur.execute("SELECT id FROM drivers WHERE id = ?", (d_id,)).fetchone()
    photo_path = f"https://images.unsplash.com/photo-{1507003211169 + d_id * 1000}?w=150&h=150&fit=crop&crop=face"
    cccd_path = f"/images/docs/cccd_driver_{d_id}.jpg"
    gplx_path = f"/images/docs/gplx_driver_{d_id}.jpg"
    
    if d_row:
        cur.execute("""
            UPDATE drivers SET 
                name = ?, phone = ?, email = ?, license_number = ?, license_class = ?,
                years_experience = ?, license_expires_at = ?, user_id = ?, cccd_number = ?,
                photo_path = ?, cccd_path = ?, gplx_path = ?, avatar = ?, status = 'ACTIVE'
            WHERE id = ?
        """, (name, phone, email, gplx, g_class, exp, exp_date, u_id, cccd, photo_path, cccd_path, gplx_path, photo_path, d_id))
    else:
        cur.execute("""
            INSERT INTO drivers (
                id, name, phone, email, license_number, license_class, years_experience,
                license_expires_at, user_id, cccd_number, photo_path, cccd_path, gplx_path,
                avatar, avg_rating, total_trips, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 4.9, 24, 'ACTIVE', '2026-10-01 08:00:00', '2026-10-01 08:00:00')
        """, (d_id, name, phone, email, gplx, g_class, exp, exp_date, u_id, cccd, photo_path, cccd_path, gplx_path, photo_path))

print(f"-> Đã đồng bộ 10 Bác Tài với 10 tài khoản User Driver (Mật khẩu: 123456).")

# 2. Tạo 40 Khách hàng (Customers) mẫu có thật trong User table
customers_data = [
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

customer_user_ids = []
for c_name, c_phone, c_email in customers_data:
    row = cur.execute("SELECT id FROM users WHERE phone = ? OR email = ?", (c_phone, c_email)).fetchone()
    if row:
        u_id = row[0]
        cur.execute("UPDATE users SET name = ?, phone = ?, email = ?, role = 'customer' WHERE id = ?", (c_name, c_phone, c_email, u_id))
    else:
        cur.execute("""
            INSERT INTO users (name, phone, email, role, password, status, created_at, updated_at)
            VALUES (?, ?, ?, 'customer', ?, 'ACTIVE', '2026-10-01 09:00:00', '2026-10-01 09:00:00')
        """, (c_name, c_phone, c_email, bcrypt_123456))
        u_id = cur.lastrowid
    customer_user_ids.append((u_id, c_name, c_phone, c_email))

print(f"-> Đã đồng bộ {len(customer_user_ids)} khách hàng CRM trong bảng users.")

# 3. Lấy danh sách 10 xe và các tuyến đường
buses = cur.execute("SELECT id, license_plate, total_seats FROM buses ORDER BY id ASC LIMIT 10").fetchall()
routes = cur.execute("SELECT id, name, origin, destination, distance_km, estimated_hours FROM routes ORDER BY id ASC").fetchall()
conductors = cur.execute("SELECT id FROM users WHERE role = 'conductor' ORDER BY id ASC").fetchall()
conductor_ids = [c[0] for c in conductors] if conductors else [3, 4, 5, 6, 7, 8, 9, 10, 11, 12]

# Cấu hình 10 xe với 10 cặp tuyến đi và về cố định
# Mỗi xe chạy 2 chuyến/ngày (1 chuyến đi, 1 chuyến về = 20 chuyến/ngày)
# Bus 1: Đà Nẵng ⇄ Huế (Route 1) - Đi 06:30, Về 13:30
# Bus 2: Đà Nẵng ⇄ Hà Tĩnh (Route 2) - Đi 06:00, Về 16:30
# Bus 3: Đà Nẵng ⇄ Quảng Bình (Route 3) - Đi 07:00, Về 15:00
# Bus 4: Đà Nẵng ⇄ Nghệ An (Vinh) (Route 4) - Đi 07:30, Về 18:30
# Bus 5: Đà Nẵng ⇄ Quy Nhơn (Bình Định) (Route 5) - Đi 08:00, Về 15:30
# Bus 6: Đà Nẵng ⇄ Nha Trang (Route 6) - Đi 08:30, Về 20:30
# Bus 7: Đà Nẵng ⇄ Buôn Ma Thuột (Route 7) - Đi 09:00, Về 20:00
# Bus 8: Đà Nẵng ⇄ Pleiku (Gia Lai) (Route 8) - Đi 09:30, Về 19:30
# Bus 9: Đà Nẵng ⇄ Quảng Ngãi (Route 9) - Đi 10:00, Về 15:00
# Bus 10: Đà Nẵng ⇄ Kon Tum (Route 10) - Đi 10:30, Về 18:00

fleet_schedule_pairs = [
    # (bus_idx, driver_id, route_id, dep_out, arr_out, dep_ret, arr_ret, base_price)
    (1, 1, 1, "06:30:00", "09:30:00", "13:30:00", "16:30:00", 150000),
    (2, 2, 2, "06:00:00", "13:30:00", "16:30:00", "23:45:00", 350000),
    (3, 3, 3, "07:00:00", "12:00:00", "15:00:00", "20:00:00", 250000),
    (4, 4, 4, "07:30:00", "15:30:00", "18:30:00", "02:30:00", 400000),
    (5, 5, 5, "08:00:00", "13:30:00", "15:30:00", "21:00:00", 280000),
    (6, 6, 6, "08:30:00", "18:00:00", "20:30:00", "06:00:00", 420000),
    (7, 7, 7, "09:00:00", "17:30:00", "20:00:00", "04:30:00", 380000),
    (8, 8, 8, "09:30:00", "17:00:00", "19:30:00", "03:00:00", 360000),
    (9, 9, 9, "10:00:00", "13:00:00", "15:00:00", "18:00:00", 160000),
    (10, 10, 10, "10:30:00", "16:30:00", "18:00:00", "23:59:00", 320000),
]

# Xóa các chuyến cũ để tạo lại đúng 20 chuyến/ngày chuẩn xác
cur.execute("DELETE FROM booking_seats")
cur.execute("DELETE FROM bookings")
cur.execute("DELETE FROM trip_expenses")
cur.execute("DELETE FROM trip_closings")
cur.execute("DELETE FROM trips")
cur.execute("DELETE FROM reviews")

print("-> Đã xóa dữ liệu cũ để tạo mới theo cấu trúc chuẩn.")

# 4. Tạo lịch trình:
# - 3 ngày quá khứ: 2026-10-25, 2026-10-26, 2026-10-27 (COMPLETED)
# - 3 ngày cố định liên tục: 2026-10-28 (Hôm nay), 2026-10-29 (Ngày mai), 2026-10-30 (Ngày kia)
all_dates = [
    ("2026-10-25", "COMPLETED"),
    ("2026-10-26", "COMPLETED"),
    ("2026-10-27", "COMPLETED"),
    ("2026-10-28", "ACTIVE"), # Một số IN_TRANSIT, READY, SCHEDULED
    ("2026-10-29", "SCHEDULED"),
    ("2026-10-30", "SCHEDULED"),
]

trip_id_counter = 1
booking_id_counter = 1
booking_seat_id_counter = 1

created_trips = []

for date_str, period_type in all_dates:
    day_num = date_str.split("-")[2]
    print(f"--> Khởi tạo 20 chuyến xe cố định cho ngày {date_str}...")

    for (b_id, d_id, r_id, dep_out, arr_out, dep_ret, arr_ret, base_pr) in fleet_schedule_pairs:
        c_id = conductor_ids[(d_id - 1) % len(conductor_ids)]
        
        # 1. Chuyến Đi (Outbound)
        out_code = f"BH-{day_num}{b_id:02d}-01A"
        dep_time_out = f"{date_str} {dep_out}"
        arr_time_out = f"{date_str} {arr_out}"
        
        # Trạng thái theo ngày
        if period_type == "COMPLETED":
            st_out = "COMPLETED"
            dep_at_out = dep_time_out
            arr_at_out = arr_time_out
        elif period_type == "ACTIVE":
            if b_id in [1, 5, 7]:
                st_out = "IN_TRANSIT"
                dep_at_out = dep_time_out
                arr_at_out = None
            elif b_id in [2, 3, 4]:
                st_out = "BOARDING"
                dep_at_out = None
                arr_at_out = None
            else:
                st_out = "READY"
                dep_at_out = None
                arr_at_out = None
        else:
            st_out = "SCHEDULED"
            dep_at_out = None
            arr_at_out = None

        cur.execute("""
            INSERT INTO trips (
                id, trip_code, route_id, bus_id, driver_id, conductor_id,
                departure_time, arrival_time, base_price, floor1_price, floor2_price,
                floor1_seat_type, floor2_seat_type, status, current_stop_order,
                departed_at, completed_at, closed_at, closed_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Giường Nằm VIP', 'Giường Nằm Tiêu Chuẩn', ?, 1, ?, ?, ?, ?, '2026-10-24 08:00:00', '2026-10-24 08:00:00')
        """, (
            trip_id_counter, out_code, r_id, b_id, d_id, c_id,
            dep_time_out, arr_time_out, base_pr, base_pr, base_pr - 10000,
            st_out,
            dep_at_out,
            arr_at_out if st_out == "COMPLETED" else None,
            arr_at_out if st_out == "COMPLETED" else None,
            "Admin Điều Hành" if st_out == "COMPLETED" else None
        ))
        created_trips.append((trip_id_counter, out_code, r_id, b_id, d_id, c_id, dep_time_out, st_out, base_pr, False))
        trip_id_counter += 1

        # 2. Chuyến Về (Return)
        ret_code = f"BH-{day_num}{b_id:02d}-02B"
        dep_time_ret = f"{date_str} {dep_ret}"
        # Xử lý nếu thời gian đến qua ngày hôm sau
        if dep_ret > arr_ret:
            arr_date_ret = (datetime.strptime(date_str, "%Y-%m-%d") + timedelta(days=1)).strftime("%Y-%m-%d")
        else:
            arr_date_ret = date_str
        arr_time_ret = f"{arr_date_ret} {arr_ret}"

        if period_type == "COMPLETED":
            st_ret = "COMPLETED"
            dep_at_ret = dep_time_ret
            arr_at_ret = arr_time_ret
        else:
            st_ret = "SCHEDULED"
            dep_at_ret = None
            arr_at_ret = None

        cur.execute("""
            INSERT INTO trips (
                id, trip_code, route_id, bus_id, driver_id, conductor_id,
                departure_time, arrival_time, base_price, floor1_price, floor2_price,
                floor1_seat_type, floor2_seat_type, status, current_stop_order,
                departed_at, completed_at, closed_at, closed_by, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Giường Nằm VIP', 'Giường Nằm Tiêu Chuẩn', ?, 1, ?, ?, ?, ?, '2026-10-24 08:00:00', '2026-10-24 08:00:00')
        """, (
            trip_id_counter, ret_code, r_id, b_id, d_id, c_id,
            dep_time_ret, arr_time_ret, base_pr, base_pr, base_pr - 10000,
            st_ret,
            dep_at_ret,
            arr_at_ret if st_ret == "COMPLETED" else None,
            arr_at_ret if st_ret == "COMPLETED" else None,
            "Admin Điều Hành" if st_ret == "COMPLETED" else None
        ))
        created_trips.append((trip_id_counter, ret_code, r_id, b_id, d_id, c_id, dep_time_ret, st_ret, base_pr, True))
        trip_id_counter += 1

print(f"-> Tổng cộng đã tạo thành công {len(created_trips)} chuyến xe (20 chuyến/ngày x 6 ngày = 120 chuyến).")

# 5. Tạo Vé (Bookings & Seats) cho các chuyến xe với ngày đặt thực tế (realistic created_at)
# Và gán user_id chính xác cho 40 khách hàng CRM để hiển thị đúng số lần đi xe và tổng chi tiêu!
seat_names_floor1 = [f"A{i:02d}" for i in range(1, 19)]
seat_names_floor2 = [f"B{i:02d}" for i in range(1, 19)]

total_bookings_created = 0

for t_id, t_code, r_id, b_id, d_id, c_id, dep_time_str, status, base_pr, is_ret in created_trips:
    dep_dt = datetime.strptime(dep_time_str, "%Y-%m-%d %H:%M:%S")
    trip_date_str = dep_dt.strftime("%Y-%m-%d")
    
    # Số lượng vé đặt tùy thuộc vào trạng thái chuyến
    if status == "COMPLETED":
        num_booked_seats = random.randint(18, 30) # 18 - 30 ghế đã đặt
    elif status in ["IN_TRANSIT", "BOARDING"]:
        num_booked_seats = random.randint(14, 26)
    else:
        num_booked_seats = random.randint(6, 18)

    # Chọn ngẫu nhiên ghế
    all_seats = seat_names_floor1 + seat_names_floor2
    random.shuffle(all_seats)
    chosen_seats = all_seats[:num_booked_seats]

    for s_idx, seat_num in enumerate(chosen_seats):
        # Chọn ngẫu nhiên 1 trong 40 khách hàng
        cust_tuple = customer_user_ids[(t_id * 7 + s_idx) % len(customer_user_ids)]
        u_id, c_name, c_phone, c_email = cust_tuple

        # Thời gian đặt vé (trước giờ khởi hành từ 6 giờ đến 48 giờ)
        hours_before = random.randint(6, 48)
        booking_created_dt = dep_dt - timedelta(hours=hours_before)
        booking_created_str = booking_created_dt.strftime("%Y-%m-%d %H:%M:%S")

        is_floor2 = seat_num.startswith('B')
        fare = base_pr - 10000 if is_floor2 else base_pr

        # Thanh toán: 85% PAID, 15% PARTIALLY_PAID (Cọc 30%)
        is_paid = (s_idx % 6 != 0)
        p_status = "PAID" if is_paid else "PARTIALLY_PAID"
        paid_amt = fare if is_paid else int(fare * 0.3)
        p_method = "ONLINE" if is_paid else "CASH"

        # Checkin status
        if status == "COMPLETED":
            ck_status = "CHECKED_IN" if (s_idx % 12 != 0) else "NO_SHOW"
            ck_time = dep_dt.strftime("%Y-%m-%d %H:%M:%S")
        elif status == "IN_TRANSIT":
            ck_status = "CHECKED_IN" if (s_idx % 4 != 0) else "PENDING"
            ck_time = dep_dt.strftime("%Y-%m-%d %H:%M:%S") if ck_status == "CHECKED_IN" else None
        else:
            ck_status = "PENDING"
            ck_time = None

        b_code = f"TKT-{t_code}-{s_idx+1:02d}"
        qr_token = f"QR_{b_code}"

        cur.execute("""
            INSERT INTO bookings (
                id, booking_code, user_id, trip_id, booking_source,
                customer_name, customer_phone, customer_email,
                pickup_stop_id, dropoff_stop_id, pickup_order, dropoff_order,
                total_seats, total_amount, paid_amount, payment_status, payment_method,
                checkin_status, checkin_method, checked_in_at, qr_token, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'WEB', ?, ?, ?, 1, 3, 1, 5, 1, ?, ?, ?, ?, ?, 'QR_SCAN', ?, ?, ?, ?)
        """, (
            booking_id_counter, b_code, u_id, t_id,
            c_name, c_phone, c_email,
            fare, paid_amt, p_status, p_method,
            ck_status, ck_time, qr_token,
            booking_created_str, booking_created_str
        ))

        cur.execute("""
            INSERT INTO booking_seats (
                id, booking_id, trip_id, seat_number, floor, pickup_order, dropoff_order, price, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, 1, 5, ?, ?, ?)
        """, (
            booking_seat_id_counter, booking_id_counter, t_id, seat_num, 2 if is_floor2 else 1, fare,
            booking_created_str, booking_created_str
        ))

        booking_id_counter += 1
        booking_seat_id_counter += 1
        total_bookings_created += 1

print(f"-> Đã tạo thành công {total_bookings_created} vé đặt với user_id khách hàng và thời gian đặt thực tế.")

# 6. Tạo Đánh giá thật của khách hàng cho Bác tài (Reviews có phân loại 1-5 sao)
sample_reviews = [
    (5, 5, 5, "Bác tài lái xe rất êm ái và an toàn, qua đèo dốc không hề bị say xe. Thái độ phục vụ 10 điểm!"),
    (5, 5, 5, "Xe chạy đúng giờ, tài xế niềm nở, hỗ trợ xách vali rất nhiệt tình. Tuyệt vời!"),
    (5, 5, 5, "Bác tài chạy cẩn thận, không phóng nhanh vượt ẩu. Cảm giác rất an tâm khi đi đêm."),
    (4, 5, 4, "Chuyến đi thuận lợi, xe sạch sẽ, bác tài chạy chuẩn giờ giấc. Sẽ tiếp tục ủng hộ."),
    (4, 4, 4, "Tài xế lái xe an toàn, điều hòa mát lạnh. Chỉ mong có thêm điểm dừng chân lâu hơn một chút."),
    (4, 4, 4, "Bác tài phong cách chuyên nghiệp, gọi điện nhắc khách trước khi đến trạm đón 15 phút."),
    (3, 4, 3, "Chuyến đi ổn, tuy nhiên bác tài bật nhạc hơi to vào buổi trưa làm khó ngủ một chút."),
    (3, 3, 3, "Lái xe an toàn nhưng đến trạm trả khách hơi chậm 15 phút do kẹt đường."),
    (2, 3, 2, "Bác tài phanh gấp một vài lần ở đoạn cua đèo làm hành khách hơi giật mình."),
    (1, 2, 1, "Tài xế nói chuyện hơi gắt khi khách hỏi trạm dừng nghỉ. Cần cải thiện thái độ phục vụ khách hàng!"),
]

review_id_counter = 1
for d_id in range(1, 11):
    # Mỗi tài xế tạo 10 - 15 đánh giá trải đều từ 1 sao đến 5 sao
    driver_name = drivers_info[d_id - 1][1]
    num_reviews = 12 if d_id == 7 else random.randint(8, 12)
    
    for r_idx in range(num_reviews):
        rev_template = sample_reviews[r_idx % len(sample_reviews)]
        stars, safety, service, comment_text = rev_template
        
        cust = customer_user_ids[(d_id * 3 + r_idx) % len(customer_user_ids)]
        u_id, c_name, _, _ = cust
        
        rev_date = datetime(2026, 10, 24 + (r_idx % 4), random.randint(8, 20), random.randint(10, 50))
        rev_date_str = rev_date.strftime("%Y-%m-%d %H:%M:%S")

        cur.execute("""
            INSERT INTO reviews (
                id, booking_id, user_id, driver_id, rating, safety_rating, service_rating,
                comment, is_anonymous, reviewer_name, created_at, updated_at
            ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)
        """, (
            review_id_counter, u_id, d_id, stars, safety, service,
            comment_text, c_name, rev_date_str, rev_date_str
        ))
        review_id_counter += 1

    # Tính avg_rating thực tế và cập nhật lại vào drivers table
    avg_r = cur.execute("SELECT round(avg(rating), 1), count(*) FROM reviews WHERE driver_id = ?", (d_id,)).fetchone()
    completed_trips_count = cur.execute("SELECT count(*) FROM trips WHERE driver_id = ? AND status = 'COMPLETED'", (d_id,)).fetchone()[0]
    cur.execute("""
        UPDATE drivers SET avg_rating = ?, total_trips = ? WHERE id = ?
    """, (avg_r[0] or 4.8, completed_trips_count, d_id))

print(f"-> Đã tạo {review_id_counter - 1} đánh giá thật từ khách hàng (1-5 sao) cho 10 bác tài.")

# 7. Tạo Chốt ca & Chi phí (Trip Closings & Trip Expenses) cho các chuyến xe đã chạy của Lơ xe
for t_id, t_code, r_id, b_id, d_id, c_id, dep_time_str, status, base_pr, is_ret in created_trips:
    if status == "COMPLETED":
        # Doanh thu tiền mặt và tiền online thực tế từ bookings
        b_rows = cur.execute("SELECT total_amount, paid_amount, payment_status, payment_method FROM bookings WHERE trip_id = ?", (t_id,)).fetchall()
        total_pax = len(b_rows)
        cash_coll = sum(r[1] for r in b_rows if r[3] == "CASH" and r[2] == "PAID")
        online_coll = sum(r[1] for r in b_rows if r[3] == "ONLINE" and r[2] == "PAID")
        
        fuel_cost = random.randint(400000, 700000)
        toll_cost = random.randint(150000, 300000)
        total_exp = fuel_cost + toll_cost
        remitted = max(0, cash_coll - total_exp)

        # Tạo closing
        cur.execute("""
            INSERT INTO trip_closings (
                trip_id, total_revenue, total_expenses, net_profit, expected_cash_from_conductor,
                actual_cash_submitted, cash_discrepancy, closed_by, notes, trip_direction,
                audit_status, audited_by, audited_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 0, 'Nguyễn Văn Phúc (Lơ Xe Trưởng)', 'Chốt ca hành trình chuẩn', 'Chuyến Đi', 'AUDITED', 'Admin Kế Toán', ?, ?, ?)
        """, (
            t_id, cash_coll + online_coll, total_exp, (cash_coll + online_coll) - total_exp,
            cash_coll, cash_coll, dep_time_str, dep_time_str, dep_time_str
        ))

print("-> Đã tạo lịch sử chốt ca và nhật ký bàn giao doanh thu cho lơ xe.")

conn.commit()
conn.close()
print("===> HOÀN TẤT SEEDING CƠ SỞ DỮ LIỆU TOÀN DIỆN THÀNH CÔNG! <===")
