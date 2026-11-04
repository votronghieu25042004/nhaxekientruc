import sqlite3
import sys
from datetime import datetime, timedelta

sys.stdout.reconfigure(encoding='utf-8')
conn = sqlite3.connect('database/database.sqlite')
cursor = conn.cursor()

print("Applying migrations for Admin features...")

# 1. Add license_expires_at to drivers if not exists
columns = [c[1] for c in cursor.execute("PRAGMA table_info(drivers)").fetchall()]
if 'license_expires_at' not in columns:
    cursor.execute("ALTER TABLE drivers ADD COLUMN license_expires_at DATE")
    print("Added license_expires_at to drivers table.")

# Update drivers with realistic license expiration dates
# Driver 1: Expiring in 18 days (triggers < 30 days warning!)
# Driver 2: Expiring in 5 days (urgent warning!)
# Driver 3: Expired 4 days ago (expired warning!)
# Other drivers: Valid until 2027-2029
today = datetime.now()
exp_d1 = (today + timedelta(days=18)).strftime('%Y-%m-%d')
exp_d2 = (today + timedelta(days=5)).strftime('%Y-%m-%d')
exp_d3 = (today - timedelta(days=4)).strftime('%Y-%m-%d')
exp_d4 = (today + timedelta(days=240)).strftime('%Y-%m-%d')
exp_d5 = (today + timedelta(days=450)).strftime('%Y-%m-%d')
exp_d6 = (today + timedelta(days=730)).strftime('%Y-%m-%d')

cursor.execute("UPDATE drivers SET license_expires_at = ? WHERE id = 1", (exp_d1,))
cursor.execute("UPDATE drivers SET license_expires_at = ? WHERE id = 2", (exp_d2,))
cursor.execute("UPDATE drivers SET license_expires_at = ? WHERE id = 3", (exp_d3,))
cursor.execute("UPDATE drivers SET license_expires_at = ? WHERE id = 4", (exp_d4,))
cursor.execute("UPDATE drivers SET license_expires_at = ? WHERE id >= 5", (exp_d5,))

# 2. Table: pricing_rules (Cài đặt giá vé linh hoạt Lễ, Tết, Cuối tuần)
cursor.execute("""
CREATE TABLE IF NOT EXISTS pricing_rules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    rule_type TEXT NOT NULL, -- 'WEEKEND', 'HOLIDAY', 'ROUTE_SURCHARGE'
    multiplier_percent REAL DEFAULT 30, -- 30%
    is_active INTEGER DEFAULT 1,
    start_date DATE,
    end_date DATE,
    days_of_week TEXT, -- '["Fri", "Sat", "Sun"]'
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
""")

# Seed default pricing rules
if cursor.execute("SELECT COUNT(*) FROM pricing_rules").fetchone()[0] == 0:
    cursor.execute("""
    INSERT INTO pricing_rules (title, rule_type, multiplier_percent, is_active, start_date, end_date, days_of_week, description)
    VALUES 
    ('Phụ thu vé Cuối tuần (Thứ 6, Thứ 7, Chủ Nhật)', 'WEEKEND', 15, 1, NULL, NULL, '["Fri", "Sat", "Sun"]', 'Tự động áp dụng tăng giá 15% vào các ngày cuối tuần cho toàn bộ tuyến xe giường nằm'),
    ('Phụ thu Lễ Tết Nguyên Đán Bính Ngọ 2026', 'HOLIDAY', 30, 1, '2026-02-10', '2026-02-24', '[]', 'Tự động tăng 30% giá vé chiều cao điểm theo quy định Sở GTVT đối soát phụ thu chiều rỗng'),
    ('Phụ thu Giỗ Tổ Hùng Vương & 30/4 - 1/5', 'HOLIDAY', 25, 1, '2026-04-25', '2026-05-03', '[]', 'Tự động áp dụng cho các tuyến du lịch Đà Nẵng - Huế, Đà Nẵng - Quy Nhơn')
    """)

# 3. Table: vouchers (Quản lý Voucher & Khuyến mãi)
cursor.execute("""
CREATE TABLE IF NOT EXISTS vouchers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    discount_type TEXT DEFAULT 'PERCENT', -- 'PERCENT', 'FIXED'
    discount_value REAL NOT NULL,
    min_order_value REAL DEFAULT 0,
    max_discount REAL DEFAULT 100000,
    usage_limit INTEGER DEFAULT 100,
    usage_count INTEGER DEFAULT 0,
    valid_until DATE,
    is_active INTEGER DEFAULT 1,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
""")

if cursor.execute("SELECT COUNT(*) FROM vouchers").fetchone()[0] == 0:
    cursor.execute("""
    INSERT INTO vouchers (code, discount_type, discount_value, min_order_value, max_discount, usage_limit, usage_count, valid_until, is_active, description)
    VALUES
    ('BUSHUB30', 'PERCENT', 30, 200000, 80000, 200, 58, '2026-12-31', 1, 'Giảm 30% giá vé tối đa 80k cho hành khách thân thiết'),
    ('TETSUMVAY', 'FIXED', 50000, 250000, 50000, 500, 142, '2026-02-28', 1, 'Giảm trực tiếp 50.000đ khi đặt vé về quê ăn Tết'),
    ('VIPKIMCUONG', 'PERCENT', 15, 0, 100000, 9999, 314, '2026-12-31', 1, 'Ưu đãi vĩnh viễn 15% cho khách hàng hạng VIP Kim Cương'),
    ('DA_NANG_FREE', 'FIXED', 30000, 150000, 30000, 100, 23, '2026-06-30', 1, 'Voucher tri ân khách hàng tuyến Đà Nẵng - Hội An')
    """)

# 4. Table: cancellation_policies (Cấu hình chính sách hủy vé)
cursor.execute("""
CREATE TABLE IF NOT EXISTS cancellation_policies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    hours_before REAL NOT NULL,
    refund_percent REAL NOT NULL,
    fee_percent REAL NOT NULL,
    rule_name TEXT NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
""")

if cursor.execute("SELECT COUNT(*) FROM cancellation_policies").fetchone()[0] == 0:
    cursor.execute("""
    INSERT INTO cancellation_policies (hours_before, refund_percent, fee_percent, rule_name, description, is_active)
    VALUES
    (24, 100, 0, 'Hủy trước >= 24h xuất bến', 'Hoàn lại 100% tiền vé (Không mất phí hủy vé). Tiền về tài khoản trong 2-4h.', 1),
    (12, 50, 50, 'Hủy trước 12h - 24h xuất bến', 'Hoàn lại 50% tiền vé (Phí hủy vé là 50% theo quy định nhà xe).', 1),
    (0, 0, 100, 'Hủy trước < 12h hoặc sát giờ chạy', 'Không hoàn tiền vé (Phí hủy 100%). Hỗ trợ bảo lưu vé nếu có giấy xác nhận y tế.', 1)
    """)

# 5. Table: fleet_maintenance_records (Theo dõi lịch sử sửa chữa, thay phụ tùng)
cursor.execute("""
CREATE TABLE IF NOT EXISTS fleet_maintenance_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bus_id INTEGER,
    plate_number TEXT NOT NULL,
    service_title TEXT NOT NULL,
    service_type TEXT NOT NULL, -- 'TIRE', 'OIL', 'BRAKE', 'AIR_CON', 'OVERHAUL'
    cost REAL NOT NULL,
    garage_name TEXT NOT NULL,
    odo_km INTEGER,
    next_service_km INTEGER,
    service_date DATE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
""")

if cursor.execute("SELECT COUNT(*) FROM fleet_maintenance_records").fetchone()[0] == 0:
    cursor.execute("""
    INSERT INTO fleet_maintenance_records (bus_id, plate_number, service_title, service_type, cost, garage_name, odo_km, next_service_km, service_date, notes)
    VALUES
    (1, '43B-067.89', 'Thay 4 lốp Michelin X Multi Tubeless 295/80R22.5 + Thay nhớt động cơ Castrol Vecton', 'TIRE', 32500000, 'Trung tâm Lốp Michelin Hải Triều Đà Nẵng', 145200, 185200, '2026-10-20', 'Đã cân mâm bấm chì và đảo lốp 4 bánh. Động cơ êm, áp suất lốp 9.0 bar chuẩn.'),
    (2, '43B-012.34', 'Bảo dưỡng hệ thống phanh khí nén ABS + Thay má phanh đĩa trước & sau', 'BRAKE', 8400000, 'Xưởng Dịch Vụ Thaco Chu Lai Đà Nẵng', 128500, 148500, '2026-10-15', 'Phanh ăn đều, cảm biến ABS 4 bánh hoạt động ổn định, sẵn sàng chạy tour đường đèo Hải Vân.'),
    (3, '43B-099.88', 'Đại tu lốc lạnh điều hòa Denso 120.000 BTU & Nạp ga lạnh R134a', 'AIR_CON', 14200000, 'Gara Điện Lạnh Ô Tô AutoPro', 162000, 202000, '2026-10-18', 'Nhiệt độ cửa gió đạt 18°C mát lạnh toàn khoang hành khách 34 phòng.'),
    (4, '43B-056.78', 'Thay lọc dầu động cơ, lọc nhiên liệu thô/tinh và súc rửa béc phun điện tử', 'OIL', 5600000, 'Trạm Dịch Vụ Ủy Quyền Hyundai Bus', 98000, 108000, '2026-10-12', 'Tiêu hao dầu sau bảo dưỡng giảm 1.5 L/100km.')
    """)

# 6. Table: fuel_logs (Định mức tiêu hao nhiên liệu L/100km chống thất thoát)
cursor.execute("""
CREATE TABLE IF NOT EXISTS fuel_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bus_id INTEGER,
    plate_number TEXT NOT NULL,
    driver_name TEXT NOT NULL,
    trip_code TEXT NOT NULL,
    distance_km REAL NOT NULL,
    liters_filled REAL NOT NULL,
    fuel_rate_actual REAL NOT NULL, -- L/100km
    fuel_rate_standard REAL DEFAULT 22.0, -- L/100km
    discrepancy_liters REAL DEFAULT 0, -- liters over/under
    log_date DATE,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
""")

if cursor.execute("SELECT COUNT(*) FROM fuel_logs").fetchone()[0] == 0:
    cursor.execute("""
    INSERT INTO fuel_logs (bus_id, plate_number, driver_name, trip_code, distance_km, liters_filled, fuel_rate_actual, fuel_rate_standard, discrepancy_liters, log_date, notes)
    VALUES
    (1, '43B-067.89', 'Nguyễn Văn Hùng', 'BH-0039', 310, 68.2, 22.0, 22.0, 0.0, '2026-10-22', '✓ Đúng chuẩn định mức bến quy định. Cây dầu Petrolimex số 12.'),
    (2, '43B-012.34', 'Trần Đình Trọng', 'BH-0038', 350, 75.6, 21.6, 22.0, -1.4, '2026-10-21', '★ Tiết kiệm 1.4 lít so với định mức nhờ duy trì đều ga trên cao tốc.'),
    (3, '43B-099.88', 'Lê Hoàng Nam', 'BH-0037', 280, 71.4, 25.5, 22.0, 9.8, '2026-10-22', '⚠️ VƯỢT ĐỊNH MỨC +9.8L: Kẹt xe đèo Lò Xo 2 tiếng nổ máy tại chỗ chạy máy lạnh. Đã kiểm tra GPS xác nhận.'),
    (4, '43B-056.78', 'Phạm Quốc Bảo', 'BH-0036', 420, 92.4, 22.0, 22.0, 0.0, '2026-10-20', '✓ Đầy bình trước giờ xuất bến Đà Nẵng đi Buôn Ma Thuột.')
    """)

# 7. Table: fixed_fleet_expenses (Phí bến bãi, bảo hiểm, BOT VETC)
cursor.execute("""
CREATE TABLE IF NOT EXISTS fixed_fleet_expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bus_id INTEGER,
    plate_number TEXT NOT NULL,
    expense_category TEXT NOT NULL, -- 'PARKING', 'INSURANCE', 'BOT_TOLL', 'REGISTRATION'
    title TEXT NOT NULL,
    provider_name TEXT NOT NULL,
    amount REAL NOT NULL,
    billing_period TEXT,
    expiry_date DATE,
    status TEXT DEFAULT 'ACTIVE', -- 'ACTIVE', 'EXPIRING_SOON', 'EXPIRED'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
""")

if cursor.execute("SELECT COUNT(*) FROM fixed_fleet_expenses").fetchone()[0] == 0:
    cursor.execute("""
    INSERT INTO fixed_fleet_expenses (bus_id, plate_number, expense_category, title, provider_name, amount, billing_period, expiry_date, status)
    VALUES
    (1, '43B-067.89', 'PARKING', 'Phí bến đỗ & xuất bến cố định tháng 10/2026', 'Bến xe Trung Tâm Đà Nẵng', 4500000, 'Tháng 10/2026', '2026-10-31', 'ACTIVE'),
    (2, '43B-012.34', 'INSURANCE', 'Bảo hiểm Trách nhiệm dân sự & Bảo hiểm Thân vỏ 1 năm', 'Tổng Công Ty Bảo Hiểm Bảo Việt', 18500000, 'Hằng năm', '2026-11-15', 'EXPIRING_SOON'),
    (3, '43B-099.88', 'BOT_TOLL', 'Nạp tiền tài khoản thu phí tự động không dừng ePass / VETC', 'Tập đoàn Viettel ePass', 5000000, 'Linh hoạt', '2026-12-31', 'ACTIVE'),
    (4, '43B-056.78', 'REGISTRATION', 'Phí đăng kiểm định kỳ xe khách & Phí bảo trì đường bộ', 'Trung tâm Đăng kiểm 43-01S', 3200000, '6 tháng/lần', '2026-11-02', 'EXPIRING_SOON')
    """)

# 8. Ensure role assignments in users table for RBAC demo
# Admin: admin@bushub.vn (Super Admin)
# Accountant: ketoan@bushub.vn (Kế toán)
# Dispatcher: dieuhanh@bushub.vn (Điều hành viên)
# Cashier: banve@bushub.vn (Nhân viên Bán vé)
cursor.execute("UPDATE users SET role = 'super_admin' WHERE id = 1")

# Create or update accountant user
cursor.execute("SELECT id FROM users WHERE email = 'ketoan@bushub.vn'")
acc = cursor.fetchone()
if not acc:
    cursor.execute("""
    INSERT INTO users (name, email, phone, role, password, created_at, updated_at)
    VALUES ('Trần Thị Mai (Kế Toán Trưởng)', 'ketoan@bushub.vn', '0905.889.901', 'accountant', '$2y$12$e6mZc7qR2rA.3q5gE.6pku1o/2P4z9bA0rZ4P5rS4.d2n4x9u6g8O', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    """)

# Create or update dispatcher user
cursor.execute("SELECT id FROM users WHERE email = 'dieuhanh@bushub.vn'")
disp = cursor.fetchone()
if not disp:
    cursor.execute("""
    INSERT INTO users (name, email, phone, role, password, created_at, updated_at)
    VALUES ('Lê Văn Hùng (Trưởng Ban Điều Hành)', 'dieuhanh@bushub.vn', '0905.778.899', 'dispatcher', '$2y$12$e6mZc7qR2rA.3q5gE.6pku1o/2P4z9bA0rZ4P5rS4.d2n4x9u6g8O', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    """)

# Create or update cashier user
cursor.execute("SELECT id FROM users WHERE email = 'banve@bushub.vn'")
cash = cursor.fetchone()
if not cash:
    cursor.execute("""
    INSERT INTO users (name, email, phone, role, password, created_at, updated_at)
    VALUES ('Nguyễn Thảo Vy (Giao Dịch Viên Quầy POS)', 'banve@bushub.vn', '0905.667.788', 'cashier', '$2y$12$e6mZc7qR2rA.3q5gE.6pku1o/2P4z9bA0rZ4P5rS4.d2n4x9u6g8O', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    """)

conn.commit()
conn.close()
print("Migration completed successfully!")
