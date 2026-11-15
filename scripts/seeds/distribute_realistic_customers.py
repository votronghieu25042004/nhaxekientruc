import sqlite3
import random
import sys

sys.stdout.reconfigure(encoding='utf-8')

conn = sqlite3.connect('database/database.sqlite')
cur = conn.cursor()

# 1. Fetch all customer users
all_customers = cur.execute("""
    SELECT id, name, phone, email 
    FROM users 
    WHERE role = 'customer' 
    ORDER BY id ASC
""").fetchall()

print(f"Total customer users: {len(all_customers)}")

# Separate user 124 (Võ Trọng Hiếu)
user_hieu = None
other_customers = []
for c in all_customers:
    if c[2] == 'tronghieuvo99@gmail.com' or c[3] == 'tronghieuvo99@gmail.com' or c[0] == 124:
        user_hieu = c
    else:
        other_customers.append(c)

if not user_hieu:
    user_hieu = (124, 'Võ Trọng Hiếu', '0912345678', 'tronghieuvo99@gmail.com')

random.seed(2026)
random.shuffle(other_customers)

# Target assignments:
# - User 124 (Võ Trọng Hiếu): 28 trips (VIP Kim Cương, 6.058 Điểm)
# - 12 Diamond customers: 15 to 22 trips
# - 25 Gold customers: 7 to 14 trips
# - 45 Silver customers: 3 to 6 trips
# - 60 Bronze customers: 1 to 2 trips
# - Remaining ~72 customers: 0 trips (registered, no booking yet)

user_target_map = {}
user_target_map[user_hieu[0]] = (user_hieu, 28)

idx = 0
# 12 Diamond
for c in other_customers[idx:idx+12]:
    user_target_map[c[0]] = (c, random.randint(15, 22))
idx += 12

# 25 Gold
for c in other_customers[idx:idx+25]:
    user_target_map[c[0]] = (c, random.randint(7, 14))
idx += 25

# 45 Silver
for c in other_customers[idx:idx+45]:
    user_target_map[c[0]] = (c, random.randint(3, 6))
idx += 45

# 60 Bronze
for c in other_customers[idx:idx+60]:
    user_target_map[c[0]] = (c, random.randint(1, 2))
idx += 60

# Remaining have 0 trips (not in user_target_map)
print(f"Assigned targets for {len(user_target_map)} active customers. Remaining {len(other_customers) - idx} have 0 trips.")

# Build assignment pool: list of (user_id, name, phone, email)
assignment_pool = []
for u_id, (user_info, count) in user_target_map.items():
    for _ in range(count):
        assignment_pool.append(user_info)

random.shuffle(assignment_pool)
print(f"Total user bookings to allocate: {len(assignment_pool)}")

# 2. Fetch all bookings ordered by trip_id, created_at
bookings = cur.execute("SELECT id, trip_id, total_amount, paid_amount FROM bookings ORDER BY trip_id ASC, id ASC").fetchall()
print(f"Total bookings in DB: {len(bookings)}")

guest_names = [
    ("Nguyễn Hoàng Nam", "0905111222", "nam.hoang@gmail.com"),
    ("Trần Đình Khang", "0905222333", "khang.tran@gmail.com"),
    ("Lê Thị Hương", "0905333444", "huong.le@gmail.com"),
    ("Phạm Minh Trí", "0905444555", "tri.pham@gmail.com"),
    ("Vũ Thanh Tùng", "0905555666", "tung.vu@gmail.com"),
    ("Đỗ Mỹ Hạnh", "0905666777", "hanh.do@gmail.com"),
    ("Bùi Quốc Việt", "0905777888", "viet.bui@gmail.com"),
    ("Hoàng Kim Oanh", "0905888999", "oanh.hoang@gmail.com"),
    ("Ngô Bảo Châu", "0905999000", "chau.ngo@gmail.com"),
    ("Dương Quốc Bảo", "0905000111", "bao.duong@gmail.com"),
    ("Phan Thúy Vy", "0905121314", "vy.phan@gmail.com"),
    ("Mai Văn Sơn", "0905232425", "son.mai@gmail.com"),
]

# Update bookings
pool_idx = 0
for b in bookings:
    b_id, t_id, tot_amt, paid_amt = b
    if pool_idx < len(assignment_pool):
        u_info = assignment_pool[pool_idx]
        u_id, u_name, u_phone, u_email = u_info
        pool_idx += 1
        cur.execute("""
            UPDATE bookings 
            SET user_id = ?, customer_name = ?, customer_phone = ?, customer_email = ?
            WHERE id = ?
        """, (u_id, u_name, u_phone, u_email, b_id))
    else:
        # Non-user guest passenger
        g_name, g_phone, g_email = random.choice(guest_names)
        cur.execute("""
            UPDATE bookings 
            SET user_id = NULL, customer_name = ?, customer_phone = ?, customer_email = ?
            WHERE id = ?
        """, (g_name, g_phone, g_email, b_id))

conn.commit()

# Update loyalty points and tiers in users table
cur.execute("UPDATE users SET reward_points = 0, loyalty_tier = 'BRONZE' WHERE role = 'customer'")

for u_id, (u_info, count) in user_target_map.items():
    spent = cur.execute("SELECT SUM(total_amount) FROM bookings WHERE user_id = ?", (u_id,)).fetchone()[0] or 0
    points = int(spent / 1000)
    
    if u_id == 124 or u_info[3] == 'tronghieuvo99@gmail.com':
        tier = 'DIAMOND'
        points = 6058
    elif count >= 15 or spent >= 4500000:
        tier = 'DIAMOND'
    elif count >= 7 or spent >= 2000000:
        tier = 'GOLD'
    elif count >= 3 or spent >= 800000:
        tier = 'SILVER'
    else:
        tier = 'BRONZE'

    cur.execute("UPDATE users SET reward_points = ?, loyalty_tier = ? WHERE id = ?", (points, tier, u_id))

conn.commit()

# Verify stats
tier_stats = cur.execute("""
    SELECT loyalty_tier, COUNT(*) 
    FROM users 
    WHERE role = 'customer' 
    GROUP BY loyalty_tier
""").fetchall()

print("Customer Loyalty Tier Distribution:")
for t in tier_stats:
    print(f"  {t[0]}: {t[1]} customers")

booking_counts = cur.execute("""
    SELECT u.id, u.name, u.email, u.loyalty_tier, u.reward_points, COUNT(b.id) as b_cnt, SUM(b.total_amount) as total_spent
    FROM users u
    LEFT JOIN bookings b ON b.user_id = u.id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY b_cnt DESC
""").fetchall()

print("\nTop 5 customers:")
for r in booking_counts[:5]:
    print(r)

print("\nBottom 5 customers:")
for r in booking_counts[-5:]:
    print(r)

conn.close()
print("Successfully distributed realistic customers!")
