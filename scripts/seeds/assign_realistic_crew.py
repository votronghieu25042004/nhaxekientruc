import sqlite3
import sys

sys.stdout.reconfigure(encoding='utf-8')

conn = sqlite3.connect('database/database.sqlite')
cur = conn.cursor()

# Hash for password '1'
hashed_pwd = '$2y$12$bx4qFZs5ZVepewhZzin0XOOpOcStwcr.QvQPy6wAbhM6Jp/yvA4Ey'

# 1. Ensure driver1..driver6 and love1..loxe6 exist in users table
drivers_to_ensure = [
    (120, 'Bùi Tiến Dũng (Bác Tài 1)', '0905789012', 'driver1@gmail.com', 'driver', 7),
    (121, 'Trần Đình Trọng (Bác Tài 2)', '0905890123', 'driver2@gmail.com', 'driver', 8),
    (125, 'Lê Hoàng Nam (Bác Tài 3)', '0905345678', 'driver3@gmail.com', 'driver', 3),
    (127, 'Phạm Quốc Bảo (Bác Tài 4)', '0905456789', 'driver4@gmail.com', 'driver', 4),
    (128, 'Hoàng Minh Tuấn (Bác Tài 5)', '0905567890', 'driver5@gmail.com', 'driver', 5),
    (129, 'Đặng Văn Lâm (Bác Tài 6)', '0905678901', 'driver6@gmail.com', 'driver', 6),
]

for uid, name, phone, email, role, did in drivers_to_ensure:
    existing = cur.execute("SELECT id FROM users WHERE email = ?", (email,)).fetchone()
    if existing:
        cur.execute("""
            UPDATE users 
            SET name = ?, phone = ?, password = ?, role = ?, driver_id = ?, status = 'ACTIVE' 
            WHERE email = ?
        """, (name, phone, hashed_pwd, role, did, email))
    else:
        cur.execute("""
            INSERT INTO users (name, email, phone, role, password, status, driver_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'ACTIVE', ?, datetime('now'), datetime('now'))
        """, (name, email, phone, role, hashed_pwd, did))

conductors_to_ensure = [
    (122, 'Nguyễn Văn Phúc (Lơ Xe 1)', '0905123456', 'loxe1@gmail.com', 'conductor'),
    (123, 'Lê Quốc Đạt (Lơ Xe 2)', '0905456789', 'loxe2@gmail.com', 'conductor'),
    (130, 'Phạm Thành Long (Lơ Xe 3)', '0905447788', 'loxe3@gmail.com', 'conductor'),
    (131, 'Vũ Anh Tuấn (Lơ Xe 4)', '0905558899', 'loxe4@gmail.com', 'conductor'),
    (132, 'Đỗ Minh Trí (Lơ Xe 5)', '0905669900', 'loxe5@gmail.com', 'conductor'),
    (133, 'Hoàng Hải Nam (Lơ Xe 6)', '0905771122', 'loxe6@gmail.com', 'conductor'),
]

for uid, name, phone, email, role in conductors_to_ensure:
    existing = cur.execute("SELECT id FROM users WHERE email = ?", (email,)).fetchone()
    if existing:
        cur.execute("""
            UPDATE users 
            SET name = ?, phone = ?, password = ?, role = ?, status = 'ACTIVE' 
            WHERE email = ?
        """, (name, phone, hashed_pwd, role, email))
    else:
        cur.execute("""
            INSERT INTO users (name, email, phone, role, password, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'ACTIVE', datetime('now'), datetime('now'))
        """, (name, email, phone, role, hashed_pwd))

conn.commit()

# Ensure all users with password '1'
cur.execute("UPDATE users SET password = ? WHERE email LIKE '%@gmail.com' OR email LIKE '%@bushub.vn'", (hashed_pwd,))
conn.commit()

# 2. Fetch all conductor user IDs to distribute across buses
conductor_users = cur.execute("SELECT id, name, email FROM users WHERE role = 'conductor' ORDER BY id ASC").fetchall()
print(f"Total conductor users: {len(conductor_users)}")

# Group conductor IDs into pairs
cond_ids = [c[0] for c in conductor_users]

# Fetch driver IDs
driver_rows = cur.execute("SELECT id, name FROM drivers ORDER BY id ASC").fetchall()
driver_ids = [d[0] for d in driver_rows]
print(f"Total drivers in drivers table: {len(driver_ids)}")

# Driver & Conductor allocations per bus_id (10 buses):
# Bus 1 (Đà Nẵng - Huế, short): Driver 1, Conductor 122 (loxe1)
# Bus 2 (Đà Nẵng - Huế, short): Driver 2, Conductor 123 (loxe2)
# Bus 3 (Đà Nẵng - Quảng Trị, short): Driver 3, Conductor 130 (loxe3)
# Bus 4 (Quảng Trị - Đà Nẵng, short): Driver 4, Conductor 131 (loxe4)
# Bus 5 (Đà Nẵng - Quảng Bình, long 5h): Driver 5 + Driver 6, Conductor 132 (loxe5) + Conductor 133 (loxe6)
# Bus 6 (Đà Nẵng - Quy Nhơn, long 5.5h): Driver 7 + Driver 8, Conductor 4 + Conductor 5
# Bus 7 (Đà Nẵng - Buôn Ma Thuột, long 9h): Driver 9 + Driver 10, Conductor 6 + Conductor 7
# Bus 8 (Đà Nẵng - Buôn Ma Thuột, long 9h): Driver 1 + Driver 3, Conductor 8 + Conductor 9
# Bus 9 (Đà Nẵng - Huế, short): Driver 2, Conductor 10
# Bus 10 (Đà Nẵng - Quy Nhơn, long 5.5h): Driver 4 + Driver 5, Conductor 11 + Conductor 12

bus_crew_rules = {
    1: {'d1': 1, 'd2': None, 'c1': 122, 'c2': None},
    2: {'d1': 2, 'd2': None, 'c1': 123, 'c2': None},
    3: {'d1': 3, 'd2': None, 'c1': 130, 'c2': None},
    4: {'d1': 4, 'd2': None, 'c1': 131, 'c2': None},
    5: {'d1': 5, 'd2': 6,    'c1': 132, 'c2': 133},
    6: {'d1': 7, 'd2': 8,    'c1': cond_ids[0], 'c2': cond_ids[1]},
    7: {'d1': 9, 'd2': 10,   'c1': cond_ids[2], 'c2': cond_ids[3]},
    8: {'d1': 1, 'd2': 3,    'c1': cond_ids[4], 'c2': cond_ids[5]},
    9: {'d1': 2, 'd2': None, 'c1': cond_ids[6], 'c2': None},
    10: {'d1': 4, 'd2': 5,   'c1': cond_ids[7], 'c2': cond_ids[8]},
}

# Update all trips according to bus_id
for bus_id, crew in bus_crew_rules.items():
    cur.execute("""
        UPDATE trips 
        SET driver_id = ?, second_driver_id = ?, conductor_id = ?, second_conductor_id = ?
        WHERE bus_id = ?
    """, (crew['d1'], crew['d2'], crew['c1'], crew['c2'], bus_id))

# Also ensure long route trips have 2 drivers and 2 conductors even if bus changes
long_routes = cur.execute("SELECT id FROM routes WHERE estimated_hours >= 5").fetchall()
long_route_ids = [r[0] for r in long_routes]

for r_id in long_route_ids:
    cur.execute("""
        UPDATE trips 
        SET second_driver_id = CASE 
            WHEN second_driver_id IS NULL AND driver_id = 1 THEN 2
            WHEN second_driver_id IS NULL AND driver_id = 2 THEN 3
            WHEN second_driver_id IS NULL THEN 1
            ELSE second_driver_id 
        END,
        second_conductor_id = CASE
            WHEN second_conductor_id IS NULL AND conductor_id = 122 THEN 123
            WHEN second_conductor_id IS NULL AND conductor_id = 123 THEN 130
            WHEN second_conductor_id IS NULL THEN 122
            ELSE second_conductor_id
        END
        WHERE route_id = ? AND (second_driver_id IS NULL OR second_conductor_id IS NULL)
    """, (r_id,))

# 3. Fix current_stop_order on completed trips:
# Each route has 4 stops (order 0, 1, 2, 3), so final stop is order 3!
cur.execute("""
    UPDATE trips 
    SET current_stop_order = 3 
    WHERE status IN ('COMPLETED', 'CLOSED')
""")
print(f"Updated current_stop_order = 3 on completed trips: {cur.rowcount} trips updated.")

conn.commit()

# Verify
crew_stats = cur.execute("""
    SELECT t.bus_id, b.license_plate, r.name, r.estimated_hours, 
           t.driver_id, t.second_driver_id, t.conductor_id, t.second_conductor_id, COUNT(t.id)
    FROM trips t
    JOIN buses b ON b.id = t.bus_id
    JOIN routes r ON r.id = t.route_id
    GROUP BY t.bus_id
""").fetchall()

print("\nSample bus crew allocations:")
for row in crew_stats:
    is_long = "LONG (2 Drivers + 2 Conductors)" if (row[3] or 0) >= 5 else "SHORT (1 Driver + 1 Conductor)"
    print(f"Bus {row[0]} ({row[1]}): {row[2]} [{is_long}] -> Drivers: ({row[4]}, {row[5]}), Conductors: ({row[6]}, {row[7]})")

conn.close()
print("\nCrew allocation complete!")
