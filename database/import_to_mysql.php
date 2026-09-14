<?php

$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$dbName = 'futa_bus_booking';

echo "=== TỰ ĐỘNG KHỞI TẠO VÀ IMPORT DỮ LIỆU MYSQL VÀO XAMPP ===\n";

try {
    // 1. Kết nối MySQL Server
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
    ]);
    
    // 2. Tạo database nếu chưa có
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "✓ Đã tạo/kiểm tra Database `{$dbName}` thành công!\n";

    // 3. Chọn database
    $pdo->exec("USE `{$dbName}`;");

    // 4. Đọc file futa_bus_booking.sql
    $sqlFile = __DIR__ . '/futa_bus_booking.sql';
    if (!file_exists($sqlFile)) {
        die("❌ Không tìm thấy file SQL tại: {$sqlFile}\n");
    }

    $sqlContent = file_get_contents($sqlFile);
    
    // Tắt kiểm tra khóa ngoại
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    $pdo->exec($sqlContent);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

    echo "✓ Đã IMPORT thành công toàn bộ bảng & dữ liệu thật vào MySQL XAMPP!\n";
    echo "🎉 Dự án FUTA Bus Lines hiện đã chạy 100% trên MySQL XAMPP (Database: {$dbName}) giống như Homestay!\n";

} catch (PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
