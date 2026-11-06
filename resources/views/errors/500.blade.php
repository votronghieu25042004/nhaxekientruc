<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Lỗi Máy Chủ Chi Tiết</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 24px; margin: 0; }
        .box { max-width: 900px; margin: 40px auto; background: #1e293b; border-radius: 12px; padding: 24px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5); }
        h1 { color: #f87171; margin-top: 0; font-size: 22px; }
        pre { background: #0f172a; padding: 16px; border-radius: 8px; overflow-x: auto; color: #facc15; font-size: 13px; line-height: 1.5; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>⚠️ 500 - LỖI MÁY CHỦ LARAVEL</h1>
        <p><strong>Thông báo lỗi:</strong> {{ $exception?->getMessage() ?: 'Không có thông điệp cụ thể' }}</p>
        <p><strong>File:</strong> {{ $exception?->getFile() }}:{{ $exception?->getLine() }}</p>
        <pre>{{ $exception?->getTraceAsString() }}</pre>
        <a href="/" class="btn">Thử lại trang chủ</a>
    </div>
</body>
</html>
