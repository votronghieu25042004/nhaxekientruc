/**
 * GOOGLE APPS SCRIPT - EMAIL RELAY FOR NHAXEKIENTRUC (BUS TERMINAL)
 * 
 * Hướng dẫn cài đặt trong 1 phút (Miễn phí 100% - Không cần thẻ tín dụng):
 * 1. Mở trình duyệt và truy cập: https://script.google.com/
 * 2. Đăng nhập bằng Gmail của bạn (hoặc Gmail nhà xe: volananh2k4@gmail.com).
 * 3. Bấm nút "Dự án mới" (New project) ở góc trên bên trái.
 * 4. Xóa hết code cũ trong file Code.gs, dán toàn bộ đoạn mã bên dưới vào.
 * 5. Bấm nút "Triển khai" (Deploy) ở góc trên bên phải -> Chọn "Triển khai mới" (New deployment).
 *    - Chọn loại (Select type): Bấm icon bánh răng ⚙️ -> Chọn "Ứng dụng web" (Web app).
 *    - Mô tả (Description): Email Relay NhaXeKienTruc
 *    - Thực thi dưới dạng (Execute as): "Tôi" (Me - your email)
 *    - Ai có quyền truy cập (Who has access): "Bất kỳ ai" (Anyone)   <-- QUAN TRỌNG NHẤT!
 * 6. Bấm "Triển khai" (Deploy) -> Cấp quyền truy cập Google nếu được hỏi.
 * 7. Copy đường link URL Web App (có dạng: https://script.google.com/macros/s/AKfycb.../exec).
 * 8. Vào Render Dashboard (https://dashboard.render.com/) -> Chọn Web Service của bạn -> Tab "Environment":
 *    - Thêm biến môi trường:
 *      Key: EMAIL_WEBHOOK_URL
 *      Value: [Dán link Web App vừa copy vào đây]
 *    - Bấm "Save Changes".
 * 
 * Kể từ lúc này, toàn bộ email trên web live Render (OTP đăng ký, vé đặt thành công, nhắc giờ xuất bến, hoàn vé...)
 * sẽ được gửi thật 100% qua Google Server tới tất cả hành khách!
 */

function doPost(e) {
  try {
    var contents = e.postData.contents;
    var data = JSON.parse(contents);

    var to = data.to;
    var subject = data.subject || "Thông báo từ Nhà Xe Kiến Trúc";
    var html = data.html || data.message || "";
    var text = data.text || "";

    if (!to) {
      return ContentService.createTextOutput(JSON.stringify({ 
        success: false, 
        error: "Missing recipient 'to' address" 
      })).setMimeType(ContentService.MimeType.JSON);
    }

    // Gửi email thật qua hạ tầng máy chủ Gmail của Google
    GmailApp.sendEmail(to, subject, text, {
      htmlBody: html,
      name: "Trường ĐH Kiến Trúc Đà Nẵng - nhaxekientruc"
    });

    return ContentService.createTextOutput(JSON.stringify({ 
      success: true, 
      message: "Email sent successfully to " + to 
    })).setMimeType(ContentService.MimeType.JSON);

  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({ 
      success: false, 
      error: err.toString() 
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({
    status: "online",
    message: "Google Apps Script Email Relay for nhaxekientruc is ready!"
  })).setMimeType(ContentService.MimeType.JSON);
}
