# 🚍 BusHub - Cổng Thông Tin & Quản Lý Bến Xe Khách Liên Tỉnh

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel" />
  <img src="https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white" alt="Vue 3" />
  <img src="https://img.shields.io/badge/Inertia.js-SPA-9553E9?style=for-the-badge&logo=inertia&logoColor=white" alt="Inertia.js" />
  <img src="https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS" />
  <img src="https://img.shields.io/badge/AI_Assistant-Integrated-2563EB?style=for-the-badge&logo=openai&logoColor=white" alt="AI Assistant" />
</p>

---

## 📖 1. Giới thiệu tổng quan
**BusHub** là nền tảng số hóa quản trị và điều hành toàn diện dành cho **Bến xe khách liên tỉnh**. Hệ thống kết nối đa chiều giữa **Ban Quản Lý Bến Xe**, **Các Nhà Xe / Đội Xe**, **Tài Xế**, **Nhân Viên Điều Phối/Lơ Xe** và **Hành Khách**.

### Điểm nổi bật của BusHub:
- 🏢 **Mô hình Bến xe số hóa**: Quản lý nhiều nhà xe, nhiều tuyến đường liên tỉnh, luồng xe xuất bến và luồng khách ra vào bến theo thời gian thực.
- 🎟️ **Đặt vé trực tuyến thông minh**: Sơ đồ chọn chỗ 2 tầng trực quan, chọn điểm đón/trả chi tiết tại từng trạm, hỗ trợ đặt cọc linh hoạt 30% và xuất vé điện tử kèm mã QR.
- 📷 **Soát vé Camera QR siêu tốc**: Tích hợp quét vé bằng camera trực tiếp trên điện thoại/máy tính cho tài xế và tiếp viên bến.
- 🤖 **Trợ lý AI BusHub thông minh**: Hỗ trợ giải đáp lịch trình xuất bến, giá vé, chính sách đón trả 24/7 và tự động lưu lịch sử trò chuyện.
- 📊 **Quản lý lượt chạy & Doanh thu trong ngày**: Thống kê số khách đón, xe bị hủy, doanh thu từng xe và báo cáo tài chính trực quan.

---

## 🖥️ 2. Các phân hệ chức năng chính

| Phân hệ | Đường dẫn (Route) | Chức năng chính |
| :--- | :--- | :--- |
| **🌐 Cổng Hành Khách** | `/`, `/schedules`, `/trips` | Tìm kiếm chuyến xe, tra cứu lịch trình bến xe, chọn ghế 2 tầng, chọn điểm đón/trả, cọc 30%, nhận vé QR. |
| **👑 Ban Quản Lý Bến (Admin)** | `/admin` | Quản lý lệnh xuất bến, mạng lưới tuyến đường, quản lý nhà xe, đội xe, tài xế, đối soát doanh thu từng xe. |
| **👨‍✈️ Cổng Bác Tài** | `/driver` | Xem lịch chạy trong ngày/ngày mai, danh sách khách cần đón tại từng trạm, báo cáo chi phí, tình trạng xe. |
| **📋 Cổng Tiếp Viên & Lơ Xe** | `/conductor` | Danh sách khách lên/xuống xe theo từng trạm dừng, đón khách bắt xe dọc đường, thu tiền mặt, chốt chuyến. |
| **📷 Soát Vé QR Camera** | `/scanner` | Quét mã QR vé điện tử từ camera thời gian thực để check-in khách lên xe ngay tại cửa bến. |
| **🤖 Trợ Lý Ảo AI** | Nút nổi toàn trang | Tư vấn lộ trình, giá vé, giải đáp thắc mắc và lưu lịch sử chat liên tục. |

---

## 🛠️ 3. Công nghệ sử dụng (Tech Stack)

- **Backend:** PHP 8.2+, Laravel 11 Framework (MVC Architecture).
- **Frontend:** Vue.js 3 (Composition API & `<script setup>`).
- **Fullstack Bridge:** Inertia.js (Single Page Application - SPA mượt mà không cần tải lại trang).
- **Styling:** Tailwind CSS (Giao diện Sapphire Blue hiện đại, chuẩn Responsive).
- **Database:** SQLite (Mặc định nhúng nhẹ nhàng, sẵn sàng chuyển đổi sang MySQL / PostgreSQL).
- **Build Tool:** Vite.
- **Scanner & QR:** HTML5 Camera Scanner & QRCode Generator.

---

## 🚀 4. Hướng dẫn cài đặt & Khởi chạy

### Yêu cầu hệ thống:
- PHP >= 8.2
- Composer
- Node.js >= 18.x & NPM

### Các bước khởi chạy:

1. **Clone repository:**
   ```bash
   git clone https://github.com/votronghieu25042004/homestay-booking.git
   cd homestay-booking
   ```

2. **Cài đặt dependencies PHP & JavaScript:**
   ```bash
   composer install
   npm install
   ```

3. **Cấu hình môi trường (.env):**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Chạy Migration & Khởi tạo dữ liệu mẫu (Database Seed):**
   ```bash
   php artisan migrate --seed
   ```

5. **Biên dịch Frontend:**
   ```bash
   npm run build
   ```
   *(Hoặc chạy `npm run dev` nếu đang phát triển code).*

6. **Khởi chạy Server:**
   ```bash
   php artisan serve --port=8080
   ```
   👉 Truy cập hệ thống tại: **`http://127.0.0.1:8080`**

---

## ⚡ 5. Đăng nhập nhanh để trải nghiệm (1-Click Fast Login)

Ngay trên thanh menu trên cùng (**Top Bar**) hoặc nút tròn góc dưới bên phải màn hình, bạn có thể click để vào ngay các tài khoản mẫu:
- **Admin Bến Xe:** `admin@bushub.vn` *(Quyền Super Admin)*
- **Bác Tài:** `driver@bushub.vn` *(Quyền Tài xế bến xe)*
- **Tiếp Viên / Lơ Xe:** `staff@bushub.vn` *(Quyền Tiếp viên điều phối bến)*

---

## 📄 Bản quyền & Đóng góp
Dự án được phát triển và quản lý bởi **BusHub Team**. Mọi quyền được bảo lưu.
