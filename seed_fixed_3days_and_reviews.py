import sqlite3
import random
from datetime import datetime, timedelta

con = sqlite3.connect('database/database.sqlite')
cur = con.cursor()

print("1. Updating all buses to ACTIVE...")
cur.execute("UPDATE buses SET status = 'ACTIVE'")

# Get conductors from users table
conductor_rows = cur.execute("SELECT id, name FROM users WHERE role = 'conductor'").fetchall()
conductor_ids = [c[0] for c in conductor_rows] if conductor_rows else [3, 4, 5, 6]
print(f"Found {len(conductor_ids)} conductors.")

drivers = cur.execute("SELECT id, name, phone FROM drivers").fetchall()
buses = cur.execute("SELECT id, license_plate, bus_type, total_seats FROM buses").fetchall()
routes = cur.execute("SELECT id, name, origin, destination, distance_km FROM routes WHERE id <= 10 OR id IN (21, 22, 25, 26)").fetchall()

print(f"Found {len(buses)} buses, {len(drivers)} drivers, {len(routes)} routes.")

# Let's clean up existing trips from 2026-10-25 to 2026-10-31 to replace with clean comprehensive schedule
old_trips = cur.execute("SELECT id FROM trips WHERE date(departure_time) BETWEEN '2026-10-25' AND '2026-10-31'").fetchall()
old_trip_ids = [t[0] for t in old_trips]
if old_trip_ids:
    q_marks = ','.join('?' * len(old_trip_ids))
    cur.execute(f"DELETE FROM booking_seats WHERE trip_id IN ({q_marks})", old_trip_ids)
    cur.execute(f"DELETE FROM bookings WHERE trip_id IN ({q_marks})", old_trip_ids)
    cur.execute(f"DELETE FROM trip_closings WHERE trip_id IN ({q_marks})", old_trip_ids)
    cur.execute(f"DELETE FROM trip_expenses WHERE trip_id IN ({q_marks})", old_trip_ids)
    cur.execute(f"DELETE FROM trips WHERE id IN ({q_marks})", old_trip_ids)
    print(f"Cleaned {len(old_trip_ids)} old trips for fresh seeding.")

# Route templates
route_map = {
    'DN_HUE': 1,
    'HUE_DN': 2,
    'DN_QB': 5,
    'QB_DN': 6,
    'DN_QN': 7,
    'QN_DN': 8,
    'DN_BMT': 9,
    'BMT_DN': 10,
    'DN_HN': 21,
    'HN_DN': 22,
}

bus_plans = [
    # (bus_id, driver_id, route_out, time_out, route_back, time_back, base_price)
    (1, 7, route_map['DN_HUE'], "06:30", route_map['HUE_DN'], "14:00", 150000), # Driver 7: Bùi Tiến Dũng
    (2, 1, route_map['DN_QN'], "07:00", route_map['QN_DN'], "15:30", 250000),  # Driver 1: Nguyễn Văn Hùng
    (3, 2, route_map['DN_QB'], "08:00", route_map['QB_DN'], "17:00", 280000),  # Driver 2: Trần Đình Trọng
    (4, 3, route_map['DN_BMT'], "08:30", route_map['BMT_DN'], "20:00", 380000), # Driver 3: Lê Hoàng Nam
    (5, 4, route_map['DN_HN'], "09:00", route_map['HN_DN'], "21:30", 450000),  # Driver 4: Phạm Quốc Bảo
    (6, 5, route_map['DN_HUE'], "10:30", route_map['HUE_DN'], "16:30", 150000), # Driver 5: Hoàng Minh Tuấn
    (7, 6, route_map['DN_QN'], "11:30", route_map['QN_DN'], "19:00", 250000),  # Driver 6: Đặng Văn Lâm
    (8, 7, route_map['DN_QB'], "13:30", route_map['QB_DN'], "21:00", 280000),  # Driver 7: Bùi Tiến Dũng (ca 2)
    (9, 8, route_map['DN_BMT'], "14:30", route_map['BMT_DN'], "22:30", 380000), # Driver 8: Hồ Tấn Tài
    (10, 9, route_map['DN_HUE'], "17:00", route_map['HUE_DN'], "21:30", 150000),# Driver 9: Vũ Văn Thanh
]

customer_names = [
    ("Nguyễn Văn An", "0905111222", "an.nguyen@gmail.com"),
    ("Trần Thị Mai", "0905222333", "mai.tran@gmail.com"),
    ("Lê Hoàng Quân", "0905333444", "quan.le@gmail.com"),
    ("Phạm Minh Tuấn", "0905444555", "tuan.pham@gmail.com"),
    ("Đỗ Thu Hương", "0905555666", "huong.do@gmail.com"),
    ("Vũ Thanh Tùng", "0905666777", "tung.vu@gmail.com"),
    ("Bùi Diệu Linh", "0905777888", "linh.bui@gmail.com"),
    ("Hoàng Kim Oanh", "0905888999", "oanh.hoang@gmail.com"),
    ("Dương Quốc Khánh", "0905999000", "khanh.duong@gmail.com"),
    ("Lý Hải Đăng", "0905123789", "dang.ly@gmail.com"),
    ("Ngô Bảo Châu", "0905987123", "chau.ngo@gmail.com"),
    ("Trương Tấn Sang", "0905456321", "sang.truong@gmail.com"),
]

# Stops map per route
route_stops = {}
for r_id in cur.execute("SELECT id FROM routes").fetchall():
    r_id = r_id[0]
    stops = cur.execute("SELECT id, stop_name, stop_order FROM route_stops WHERE route_id = ? ORDER BY stop_order ASC", (r_id,)).fetchall()
    route_stops[r_id] = stops

# 2. Seed past days (2026-10-25, 2026-10-26, 2026-10-27) - COMPLETED TRIPS for Driver history
print("2. Seeding completed historical trips for past 3 days (2026-10-25 to 2026-10-27)...")
for day_offset in [-3, -2, -1]:
    target_date = (datetime(2026, 10, 28) + timedelta(days=day_offset)).strftime('%Y-%m-%d')
    date_code = target_date.replace('-', '')[4:]
    
    for p_idx, plan in enumerate(bus_plans):
        bus_id = plan[0]
        driver_id = plan[1]
        conductor_id = conductor_ids[p_idx % len(conductor_ids)]
        r_out = plan[2]
        t_out = plan[3]
        base_price = plan[6]
        
        # Outbound trip (COMPLETED)
        trip_code = f"BH-{date_code}-{p_idx+1:02d}A"
        dep_dt = f"{target_date} {t_out}:00"
        arr_dt = f"{target_date} {int(t_out.split(':')[0])+3:02d}:30:00"
        
        cur.execute("""
            INSERT INTO trips (trip_code, route_id, bus_id, driver_id, conductor_id, departure_time, arrival_time,
                               base_price, floor1_price, floor2_price, status, current_stop_order, departed_at,
                               completed_at, closed_at, closed_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'COMPLETED', 5, ?, ?, ?, 'Admin Điều Hành', datetime('now'), datetime('now'))
        """, (trip_code, r_out, bus_id, driver_id, conductor_id, dep_dt, arr_dt, base_price, base_price, base_price - 10000,
              dep_dt, arr_dt, arr_dt))
        trip_id = cur.lastrowid
        
        # Trip closing
        cur.execute("""
            INSERT INTO trip_closings (trip_id, total_revenue, total_expenses, net_profit, expected_cash_from_conductor,
                                       actual_cash_submitted, cash_discrepancy, closed_by, audit_status, created_at, updated_at)
            VALUES (?, 5600000, 450000, 5150000, 2100000, 2100000, 0, 'Nguyễn Văn Phúc (Lơ xe)', 'AUDITED', datetime('now'), datetime('now'))
        """, (trip_id,))
        
        # Add 6 completed bookings for this trip
        stops = route_stops.get(r_out, [])
        p_stop = stops[0][0] if stops else None
        d_stop = stops[-1][0] if stops else None
        
        for b_i in range(6):
            c_info = customer_names[(p_idx * 2 + b_i) % len(customer_names)]
            seat_no = f"A{b_i+1:02d}"
            b_code = f"TKT-{date_code}-{trip_id}-{b_i+1:02d}"
            cur.execute("""
                INSERT INTO bookings (booking_code, trip_id, booking_source, customer_name, customer_phone, customer_email,
                                      pickup_stop_id, dropoff_stop_id, pickup_order, dropoff_order, total_seats, total_amount,
                                      paid_amount, payment_status, payment_method, checkin_status, checkin_method, checked_in_at,
                                      qr_token, created_at, updated_at)
                VALUES (?, ?, 'WEB', ?, ?, ?, ?, ?, 1, 5, 1, ?, ?, 'PAID', 'ONLINE', 'CHECKED_IN', 'QR_SCAN', ?, ?, datetime('now'), datetime('now'))
            """, (b_code, trip_id, c_info[0], c_info[1], c_info[2], p_stop, d_stop, base_price, base_price, dep_dt, f"QR_{b_code}"))
            b_id = cur.lastrowid
            
            cur.execute("""
                INSERT INTO booking_seats (booking_id, trip_id, seat_number, floor, pickup_order, dropoff_order, price, created_at, updated_at)
                VALUES (?, ?, ?, 1, 1, 5, ?, datetime('now'), datetime('now'))
            """, (b_id, trip_id, seat_no, base_price))

# 3. Seed future continuous 3-day schedule: Today (2026-10-28), Tomorrow (2026-10-29), Day After Tomorrow (2026-10-30)
print("3. Seeding continuous 3-day fixed schedule for all 10 buses (2026-10-28 to 2026-10-30)...")
days = [
    ('2026-10-28', 'TODAY'),
    ('2026-10-29', 'TOMORROW'),
    ('2026-10-30', 'AFTER_TOMORROW'),
]

for d_idx, (t_date, label) in enumerate(days):
    d_code = t_date.replace('-', '')[4:]
    for p_idx, plan in enumerate(bus_plans):
        bus_id = plan[0]
        driver_id = plan[1]
        conductor_id = conductor_ids[p_idx % len(conductor_ids)]
        r_out = plan[2]
        t_out = plan[3]
        r_back = plan[4]
        t_back = plan[5]
        base_price = plan[6]
        
        # Status logic:
        if label == 'TODAY':
            hour = int(t_out.split(':')[0])
            if hour <= 8:
                status_out = 'IN_TRANSIT'
            elif hour <= 12:
                status_out = 'BOARDING'
            else:
                status_out = 'SCHEDULED'
            status_back = 'SCHEDULED'
        else:
            status_out = 'SCHEDULED'
            status_back = 'SCHEDULED'
        
        # Outbound Trip
        trip_code_out = f"BH-{d_code}-{bus_id:02d}A"
        dep_out = f"{t_date} {t_out}:00"
        arr_out = f"{t_date} {int(t_out.split(':')[0])+3:02d}:30:00"
        
        cur.execute("""
            INSERT INTO trips (trip_code, route_id, bus_id, driver_id, conductor_id, departure_time, arrival_time,
                               base_price, floor1_price, floor2_price, status, current_stop_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, datetime('now'), datetime('now'))
        """, (trip_code_out, r_out, bus_id, driver_id, conductor_id, dep_out, arr_out, base_price, base_price, base_price - 10000, status_out))
        t_id_out = cur.lastrowid
        
        # Inbound / Return Trip
        trip_code_back = f"BH-{d_code}-{bus_id:02d}B"
        dep_back = f"{t_date} {t_back}:00"
        arr_back = f"{t_date} {min(23, int(t_back.split(':')[0])+3):02d}:45:00"
        cur.execute("""
            INSERT INTO trips (trip_code, route_id, bus_id, driver_id, conductor_id, departure_time, arrival_time,
                               base_price, floor1_price, floor2_price, status, current_stop_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, datetime('now'), datetime('now'))
        """, (trip_code_back, r_back, bus_id, driver_id, conductor_id, dep_back, arr_back, base_price, base_price, base_price - 10000, status_back))
        t_id_back = cur.lastrowid
        
        # Add realistic bookings for Outbound trip (mix of PAID and PENDING for POS testing!)
        stops = route_stops.get(r_out, [])
        p_stop = stops[0][0] if stops else None
        d_stop = stops[-1][0] if stops else None
        
        # 5 bookings: 3 PAID, 2 PENDING (UNPAID)
        booked_seats = ['A01', 'A02', 'A05', 'B02', 'B06']
        for s_idx, seat_no in enumerate(booked_seats):
            c_info = customer_names[(p_idx + s_idx) % len(customer_names)]
            is_paid = (s_idx < 3)
            p_status = 'PAID' if is_paid else 'PENDING'
            p_method = 'ONLINE' if is_paid else 'CASH'
            paid_amt = base_price if is_paid else 0
            checkin = 'CHECKED_IN' if (status_out == 'IN_TRANSIT' and is_paid) else 'NOT_CHECKED_IN'
            
            b_code = f"POS-{d_code}-{t_id_out}-{seat_no}"
            cur.execute("""
                INSERT INTO bookings (booking_code, trip_id, booking_source, customer_name, customer_phone, customer_email,
                                      pickup_stop_id, dropoff_stop_id, pickup_order, dropoff_order, total_seats, total_amount,
                                      paid_amount, payment_status, payment_method, checkin_status, qr_token, created_at, updated_at)
                VALUES (?, ?, 'POS_COUNTER', ?, ?, ?, ?, ?, 1, 5, 1, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            """, (b_code, t_id_out, c_info[0], c_info[1], c_info[2], p_stop, d_stop, base_price, paid_amt, p_status, p_method, checkin, f"QR_{b_code}"))
            b_id = cur.lastrowid
            
            cur.execute("""
                INSERT INTO booking_seats (booking_id, trip_id, seat_number, floor, pickup_order, dropoff_order, price, created_at, updated_at)
                VALUES (?, ?, ?, ?, 1, 5, ?, datetime('now'), datetime('now'))
            """, (b_id, t_id_out, seat_no, 1 if seat_no.startswith('A') else 2, base_price))

# 4. Seed realistic customer reviews for Driver 7 (Bùi Tiến Dũng) & others (with 1 to 5 stars!)
print("4. Seeding realistic reviews (1 to 5 stars) for driver profiles...")
cur.execute("DELETE FROM reviews")

reviews_data = [
    # (driver_id, rating, safety, service, comment, reviewer, date_str)
    (7, 5, 5, 5, "Bác tài Bùi Tiến Dũng lái xe rất êm ái và an toàn, qua đèo dốc không hề bị say xe. Thái độ phục vụ 10 điểm!", "Nguyễn Thị Thu Hà", "2026-10-27 15:30:00"),
    (7, 5, 5, 5, "Xe xuất bến đúng giờ từng phút, giường nằm thơm tho sạch sẽ, bác tài chạy cẩn thận không vượt ẩu.", "Trần Văn Hùng", "2026-10-27 10:20:00"),
    (7, 5, 5, 5, "Bác tài và phụ xe rất chu đáo, phụ bê hành lý tận nơi cho mẹ già tôi. Rất tin tưởng nhà xe BusHub!", "Phạm Thu Trang", "2026-10-26 18:45:00"),
    (7, 5, 5, 5, "Chạy đêm đèn cốt chuẩn, không bóp còi inh ỏi làm khách thức giấc, xe êm ru ngủ một giấc là đến nơi.", "Lê Hoàng Long", "2026-10-26 06:15:00"),
    (7, 5, 5, 5, "Tài xế kinh nghiệm lâu năm, thái độ với khách rất lịch sự, hòa nhã. Lần sau đi công tác vẫn sẽ chọn xe này.", "Võ Thành Nam", "2026-10-25 19:10:00"),
    (7, 5, 5, 5, "Đúng chuẩn tài xế hạng nhất! Chạy đúng tốc độ biển báo, không bao giờ phóng nhanh giành đường.", "Đoàn Minh Trí", "2026-10-25 14:00:00"),
    (7, 4, 5, 4, "Tài xế lái rất an toàn, nhưng nhiệt độ điều hòa lúc 2h sáng hơi lạnh một chút, sau khi nhắc bác tài đã chỉnh lại ấm hơn.", "Hoàng Kim Yến", "2026-10-24 22:30:00"),
    (7, 4, 4, 4, "Chuyến đi ổn định, vào trạm dừng chân ăn uống sạch sẽ, thời gian nghỉ ngơi hợp lý 20 phút.", "Bùi Quang Huy", "2026-10-24 16:20:00"),
    (7, 4, 5, 4, "Bác tài vui tính, hướng dẫn khách cách chỉnh cổng sạc USB và đèn đọc sách rất tận tình.", "Ngô Mỹ Duyên", "2026-10-23 11:40:00"),
    (7, 4, 4, 4, "Lái xe mượt mà, điểm đón đúng hẹn tại ngã ba. Hài lòng về chuyến đi.", "Đinh Trọng Giáp", "2026-10-22 09:15:00"),
    (7, 3, 4, 3, "Xe bị chậm 15 phút do tắc đường đoạn quốc lộ đang thi công, tuy nhiên bác tài có thông báo xin lỗi hành khách.", "Lý Quốc Bảo", "2026-10-21 17:50:00"),
    (7, 3, 3, 3, "Tài xế lái an toàn nhưng âm lượng loa thông báo lúc dừng đón khách hơi to làm giật mình bé nhỏ.", "Phan Thị Diệu", "2026-10-20 23:10:00"),
    (7, 2, 3, 2, "Xe có dừng đón khách dọc đường mất 5 phút dù mình đặt vé muốn đi thẳng, mong nhà xe hạn chế đón vãng lai.", "Trương Văn Tấn", "2026-10-19 13:25:00"),
    (7, 1, 2, 1, "Hôm mưa gió đường gồ ghề đoạn tránh thị xã xe chạy hơi xóc, mong bác tài giảm tốc độ hơn nữa ở các gờ giảm tốc.", "Nguyễn Hữu Đạt", "2026-10-18 20:00:00"),
    
    # Reviews for Driver 1 (Nguyễn Văn Hùng)
    (1, 5, 5, 5, "Bác Hùng lái xe rất chuyên nghiệp, vui vẻ niềm nở với hành khách từ lúc lên xe tới lúc xuống.", "Vũ Đình Toàn", "2026-10-27 12:00:00"),
    (1, 4, 4, 4, "Chuyến đi Quy Nhơn êm thuận, xe sạch và bác tài thân thiện.", "Nguyễn Bảo Long", "2026-10-26 15:00:00"),
    (1, 3, 3, 3, "Bác tài chạy cẩn thận, chỉ mong xe có thêm nước lọc lạnh miễn phí.", "Trần Thị Ánh", "2026-10-25 08:30:00"),
    (1, 2, 2, 3, "Đến bến trễ 20 phút vì trời mưa to đèo Cù Mông.", "Lê Văn Hảo", "2026-10-23 17:00:00"),

    # Reviews for Driver 2 (Trần Đình Trọng)
    (2, 5, 5, 5, "Tài xế Trọng lái đèo Hải Vân cực kỳ chuẩn, cua ngọt xớt không lắc lư tí nào.", "Tạ Minh Châu", "2026-10-27 09:40:00"),
    (2, 5, 5, 5, "Rất ưng ý, xe Universe 36 chỗ chạy đầm, bác tài nhắc nhở thắt dây an toàn chu đáo.", "Hồ Xuân Hương", "2026-10-26 11:20:00"),
    (2, 4, 4, 4, "Phục vụ tốt, thái độ nhã nhặn, lái xe nghiêm túc không nghe điện thoại khi cầm lái.", "Phạm Công Danh", "2026-10-24 14:15:00"),
    (2, 1, 1, 2, "Hôm đó xe bị hỏng máy lạnh nửa tiếng đoạn Tam Kỳ, bác tài có sửa lại nhưng cảm giác hơi ngột ngạt.", "Đào Văn Bách", "2026-10-20 16:30:00"),
]

for r in reviews_data:
    cur.execute("""
        INSERT INTO reviews (booking_id, user_id, driver_id, rating, safety_rating, service_rating, comment, is_anonymous, reviewer_name, created_at, updated_at)
        VALUES (NULL, NULL, ?, ?, ?, ?, ?, 0, ?, ?, ?)
    """, (r[0], r[1], r[2], r[3], r[4], r[5], r[6], r[6]))

# Update driver avg_rating and total_trips
for d in drivers:
    d_id = d[0]
    avg_r = cur.execute("SELECT AVG(rating) FROM reviews WHERE driver_id = ?", (d_id,)).fetchone()[0] or 4.9
    trip_count = cur.execute("SELECT COUNT(*) FROM trips WHERE driver_id = ?", (d_id,)).fetchone()[0]
    cur.execute("UPDATE drivers SET avg_rating = ?, total_trips = ? WHERE id = ?", (round(avg_r, 1), trip_count, d_id))

con.commit()
con.close()
print("SEEDED SUCCESSFULLY! All 10 buses have trips, past history created, and realistic 1-5 star reviews inserted.")
