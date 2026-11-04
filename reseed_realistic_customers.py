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
# Remaining (0 trips)
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

# Also give user #3 (Trần Văn Điều Phối) 2 trips (1 completed + 1 scheduled)
customer_targets[3] = 2

b_id = 1
seat_names = [f"A{i:02d}" for i in range(1, 18)] + [f"B{i:02d}" for i in range(1, 18)]

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
        b_code = f"BH-{t_code}-{b_id:04d}"
        qr = f"QR_{b_code}"

        cur.execute("""
            INSERT INTO bookings (
                id, booking_code, user_id, trip_id, booking_source,
                customer_name, customer_phone, customer_email,
                pickup_stop_id, dropoff_stop_id, pickup_order, dropoff_order,
                total_seats, total_amount, paid_amount, payment_status, payment_method,
                checkin_status, checkin_method, checked_in_at, qr_token,
                points_earned, points_awarded, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'ONLINE', ?, ?, ?, 1, 3, 1, 5, 1, ?, ?, ?, 'ONLINE', 'PENDING', 'QR_SCAN', NULL, ?, ?, ?, ?, ?)
        """, (b_id, b_code, u_id, t_id, u_name, u_phone, u_email, fare, paid_amt, p_status, qr, pts, is_awarded, dep_time, dep_time))

        cur.execute("""
            INSERT INTO booking_seats (id, booking_id, trip_id, seat_number, floor, pickup_order, dropoff_order, price, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, 1, 5, ?, ?, ?)
        """, (b_id, b_id, t_id, seat_num, fare, dep_time, dep_time))

        b_id += 1

# Non-user guest passengers to fill bus capacity realistically
for t in trips:
    t_id, t_code, dep_time, status, base_pr = t
    num_extra = random.randint(6, 14)
    for extra_idx in range(num_extra):
        seat_num = seat_names[(extra_idx * 2) % len(seat_names)]
        fare = base_pr
        is_paid = True
        p_status = 'PAID'
        paid_amt = fare
        pts = int(paid_amt / 1000)
        is_awarded = 1 if (status == 'COMPLETED') else 0
        b_code = f"POS-{t_code}-{b_id:04d}"
        qr = f"QR_{b_code}"
        g_name = f"Khách Mua Tại Quầy {extra_idx+1}"
        g_phone = f"0988{random.randint(100000, 999999)}"

        cur.execute("""
            INSERT INTO bookings (
                id, booking_code, user_id, trip_id, booking_source,
                customer_name, customer_phone, customer_email,
                pickup_stop_id, dropoff_stop_id, pickup_order, dropoff_order,
                total_seats, total_amount, paid_amount, payment_status, payment_method,
                checkin_status, checkin_method, checked_in_at, qr_token,
                points_earned, points_awarded, created_at, updated_at
            ) VALUES (?, ?, NULL, ?, 'POS', ?, ?, 'khachquay@bushub.vn', 1, 3, 1, 5, 1, ?, ?, ?, 'CASH', 'PENDING', 'POS', NULL, ?, ?, ?, ?, ?)
        """, (b_id, b_code, t_id, g_name, g_phone, fare, paid_amt, p_status, qr, pts, is_awarded, dep_time, dep_time))

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
    total_trips = cur.execute("SELECT count(*) FROM bookings WHERE user_id = ?", (u_id,)).fetchone()[0]

    if total_trips >= 7:
        tier = 'DIAMOND'
    elif total_trips >= 4:
        tier = 'GOLD'
    elif total_trips >= 2:
        tier = 'SILVER'
    else:
        tier = 'BRONZE'

    cur.execute("UPDATE users SET reward_points = ?, loyalty_tier = ? WHERE id = ?", (pts, tier, u_id))

conn.commit()

tier_counts = cur.execute("SELECT loyalty_tier, count(*) FROM users WHERE role = 'customer' GROUP BY loyalty_tier").fetchall()
print("--> Phân bố hạng khách hàng CRM thực tế:")
for t, cnt in tier_counts:
    print(f"  * {t}: {cnt} khách hàng")

print(f"-> Tổng cộng đã tạo: {b_id - 1} lượt vé thực tế trong Database.")
conn.close()
