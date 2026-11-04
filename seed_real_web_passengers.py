import sqlite3
import random
import sys
from datetime import datetime, timedelta

sys.stdout.reconfigure(encoding='utf-8')

conn = sqlite3.connect('database/database.sqlite')
cur = conn.cursor()

# 1. Clear existing bookings & booking_seats
cur.execute('DELETE FROM booking_seats')
cur.execute('DELETE FROM bookings')
conn.commit()

# 2. Get customer users
customers = cur.execute("SELECT id, name, phone, email FROM users WHERE role = 'customer' ORDER BY id ASC").fetchall()
print(f"Total customers: {len(customers)}")

# 3. Get trips
trips = cur.execute("SELECT id, trip_code, departure_time, status, base_price FROM trips ORDER BY departure_time ASC").fetchall()
print(f"Total trips: {len(trips)}")

completed_trips = [t for t in trips if t[3] == 'COMPLETED']
scheduled_trips = [t for t in trips if t[3] in ['SCHEDULED', 'IN_TRANSIT', 'BOARDING', 'OPEN_FOR_SALE']]

# 4. Define realistic, varied customer targets
# 4 Diamond (7 - 10 trips)
# 8 Gold (4 - 6 trips)
# 18 Silver (2 - 3 trips)
# 25 Bronze (1 trip)
# 19 New Signups (0 trips)
customer_targets = {}
for c in customers[0:4]:
    customer_targets[c[0]] = random.randint(7, 10)
for c in customers[4:12]:
    customer_targets[c[0]] = random.randint(4, 6)
for c in customers[12:30]:
    customer_targets[c[0]] = random.randint(2, 3)
for c in customers[30:55]:
    customer_targets[c[0]] = 1
for c in customers[55:]:
    customer_targets[c[0]] = 0

# User #3 (Trần Văn Điều Phối) has 2 trips (1 completed + 1 scheduled)
customer_targets[3] = 2

b_id = 1
seat_names = [f"A{i:02d}" for i in range(1, 18)] + [f"B{i:02d}" for i in range(1, 18)]

# List of rich Vietnamese real passenger names for WEB online bookings
real_pax_names = [
    "Nguyễn Thị Thu Hằng", "Trần Minh Quân", "Lê Hoàng Nam", "Phạm Thu Phương",
    "Hoàng Quốc Bảo", "Vũ Đình Trọng", "Đỗ Hải Yến", "Phan Thanh Tùng",
    "Bùi Thị Lan", "Võ Trung Kiên", "Đặng Mỹ Duyên", "Dương Đức Anh",
    "Hồ Thị Hoa", "Lý Gia Huy", "Nguyễn Văn Tuấn", "Trần Bích Ngọc",
    "Lê Phương Thảo", "Phạm Hữu Thắng", "Hoàng Kim Oanh", "Vũ Ngọc Hà",
    "Đỗ Tiến Đạt", "Phan Thùy Dung", "Bùi Quang Huy", "Võ Mai Anh",
    "Đặng Tuấn Kiệt", "Dương Khánh Linh", "Hồ Trọng Nghĩa", "Lý Bảo An",
    "Nguyễn Thị Mai", "Trần Văn Hùng", "Lê Thị Thảo", "Phạm Văn Đức",
    "Hoàng Thị Linh", "Vũ Minh Khôi", "Đỗ Thị Quỳnh", "Phan Văn Cường",
    "Bùi Thị Trang", "Võ Minh Trí", "Đặng Thị Nga", "Dương Văn Phong"
]

online_pay_methods = ['VNPAY', 'MOMO', 'VIETQR', 'ONLINE_CARD']

# A. Bookings for registered users (100% WEB Online)
for u_id, target_count in customer_targets.items():
    if target_count == 0:
        continue
    u_row = cur.execute("SELECT name, phone, email FROM users WHERE id = ?", (u_id,)).fetchone()
    if not u_row:
        continue
    u_name, u_phone, u_email = u_row

    chosen_trips = []
    num_completed = max(1, int(target_count * 0.75))
    num_upcoming = target_count - num_completed

    if completed_trips:
        chosen_trips.extend(random.sample(completed_trips, min(num_completed, len(completed_trips))))
    if scheduled_trips and num_upcoming > 0:
        chosen_trips.extend(random.sample(scheduled_trips, min(num_upcoming, len(scheduled_trips))))

    for t in chosen_trips:
        t_id, t_code, dep_time, status, base_pr = t
        seat_num = random.choice(seat_names)
        fare = base_pr
        is_paid = (status == 'COMPLETED') or (random.random() > 0.15)
        p_status = 'PAID' if is_paid else 'PARTIALLY_PAID'
        paid_amt = fare if is_paid else int(fare * 0.3)
        pts = int(paid_amt / 1000)
        is_awarded = 1 if (status == 'COMPLETED' and p_status == 'PAID') else 0
        b_code = f"BH-WEB-{random.randint(1000, 9999)}-{b_id:04d}"
        qr = f"QR_{b_code}"
        pay_method = random.choice(online_pay_methods)

        cur.execute("""
            INSERT INTO bookings (
                id, booking_code, user_id, trip_id, booking_source,
                customer_name, customer_phone, customer_email,
                pickup_stop_id, dropoff_stop_id, pickup_order, dropoff_order,
                total_seats, total_amount, paid_amount, payment_status, payment_method,
                checkin_status, checkin_method, checked_in_at, qr_token,
                points_earned, points_awarded, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'WEB', ?, ?, ?, 1, 3, 1, 5, 1, ?, ?, ?, ?, 'PENDING', 'QR_SCAN', NULL, ?, ?, ?, ?, ?)
        """, (b_id, b_code, u_id, t_id, u_name, u_phone, u_email, fare, paid_amt, p_status, pay_method, qr, pts, is_awarded, dep_time, dep_time))

        cur.execute("""
            INSERT INTO booking_seats (id, booking_id, trip_id, seat_number, floor, pickup_order, dropoff_order, price, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, 1, 5, ?, ?, ?)
        """, (b_id, b_id, t_id, seat_num, fare, dep_time, dep_time))

        b_id += 1

# B. Non-user guest passengers: 85% WEB Online, 15% POS Quầy với tên người thật 100%
for t in trips:
    t_id, t_code, dep_time, status, base_pr = t
    num_extra = random.randint(6, 12)
    for extra_idx in range(num_extra):
        seat_num = seat_names[(extra_idx * 3) % len(seat_names)]
        fare = base_pr
        is_paid = True
        p_status = 'PAID'
        paid_amt = fare
        pts = int(paid_amt / 1000)
        is_awarded = 1 if (status == 'COMPLETED') else 0

        pax_name = random.choice(real_pax_names)
        pax_phone = f"09{random.choice(['05', '06', '07', '08', '12', '34'])}{random.randint(100000, 999999)}"
        email_prefix = pax_name.lower().replace(" ", "").replace("đ", "d").replace("ô", "o").replace("ê", "e").replace("à", "a").replace("á", "a").replace("ạ", "a").replace("ả", "a").replace("ã", "a").replace("â", "a").replace("ầ", "a").replace("ấ", "a").replace("ậ", "a").replace("ẩ", "a").replace("ẫ", "a").replace("è", "e").replace("é", "e").replace("ẹ", "e").replace("ẻ", "e").replace("ẽ", "e").replace("ì", "i").replace("í", "i").replace("ị", "i").replace("ỉ", "i").replace("ĩ", "i").replace("ò", "o").replace("ó", "o").replace("ọ", "o").replace("ỏ", "o").replace("õ", "o").replace("ù", "u").replace("ú", "u").replace("ụ", "u").replace("ủ", "u").replace("ũ", "u").replace("ư", "u").replace("ừ", "u").replace("ứ", "u").replace("ự", "u").replace("ử", "u").replace("ữ", "u").replace("ỳ", "y").replace("ý", "y").replace("ỵ", "y").replace("ỷ", "y").replace("ỹ", "y")
        pax_email = f"{email_prefix}{random.randint(10, 99)}@gmail.com"

        # 85% WEB, 15% POS
        is_web = random.random() < 0.85
        if is_web:
            source = 'WEB'
            b_code = f"BH-WEB-{t_code}-{b_id:04d}"
            pay_method = random.choice(online_pay_methods)
            checkin_method = 'QR_SCAN'
        else:
            source = 'POS'
            b_code = f"POS-{t_code}-{b_id:04d}"
            pay_method = 'CASH'
            checkin_method = 'POS'
            pax_name = f"{pax_name} (Vé Quầy)"

        qr = f"QR_{b_code}"

        cur.execute("""
            INSERT INTO bookings (
                id, booking_code, user_id, trip_id, booking_source,
                customer_name, customer_phone, customer_email,
                pickup_stop_id, dropoff_stop_id, pickup_order, dropoff_order,
                total_seats, total_amount, paid_amount, payment_status, payment_method,
                checkin_status, checkin_method, checked_in_at, qr_token,
                points_earned, points_awarded, created_at, updated_at
            ) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, 1, 3, 1, 5, 1, ?, ?, ?, ?, 'PENDING', ?, NULL, ?, ?, ?, ?, ?)
        """, (b_id, b_code, t_id, source, pax_name, pax_phone, pax_email, fare, paid_amt, p_status, pay_method, checkin_method, qr, pts, is_awarded, dep_time, dep_time))

        cur.execute("""
            INSERT INTO booking_seats (id, booking_id, trip_id, seat_number, floor, pickup_order, dropoff_order, price, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, 1, 5, ?, ?, ?)
        """, (b_id, b_id, t_id, seat_num, fare, dep_time, dep_time))

        b_id += 1

conn.commit()

# 5. Update user reward points and tier
all_users_to_update = customers + [(3, 'Trần Văn Điều Phối', '0904445566', 'staff@bushub.vn')]
for u in all_users_to_update:
    u_id = u[0]
    res = cur.execute("SELECT sum(points_earned), count(*) FROM bookings WHERE user_id = ? AND points_awarded = 1", (u_id,)).fetchone()
    pts = res[0] or 0
    cnt = res[1] or 0

    if cnt >= 7 or pts >= 1800:
        tier = 'DIAMOND'
    elif cnt >= 4 or pts >= 800:
        tier = 'GOLD'
    elif cnt >= 2 or pts >= 300:
        tier = 'SILVER'
    else:
        tier = 'MEMBER'

    cur.execute("UPDATE users SET reward_points = ?, loyalty_tier = ? WHERE id = ?", (pts, tier, u_id))

conn.commit()

web_count = cur.execute("SELECT count(*) FROM bookings WHERE booking_source = 'WEB'").fetchone()[0]
pos_count = cur.execute("SELECT count(*) FROM bookings WHERE booking_source = 'POS'").fetchone()[0]
total_count = cur.execute("SELECT count(*) FROM bookings").fetchone()[0]
print(f"Reseed SUCCESS! Total Bookings: {total_count} (WEB: {web_count} ({web_count*100//total_count}%), POS: {pos_count} ({pos_count*100//total_count}%))")

conn.close()
