# 🔔 Hệ thống thông báo đặt bàn tự động

## 🚀 Khởi động nhanh

### **Chạy scheduler (chọn 1 trong 3 cách):**

#### Cách 1: PowerShell (Khuyên dùng) ⭐
```powershell
.\run-scheduler.ps1
```

#### Cách 2: BAT file
```
Double-click vào run-scheduler.bat
```

#### Cách 3: Thủ công
```bash
php artisan schedule:run
```

---

## 📋 Chức năng

Hệ thống tự động kiểm tra và gửi thông báo mỗi phút:

- ⏰ **Booking sắp tới** (trong vòng 15 phút) → Thông báo màu vàng
- ⚠️ **Booking đã trễ** (quá giờ đặt) → Thông báo màu đỏ

Thông báo hiển thị trên **icon chuông 🔔** ở góc trên bên phải trong Filament Admin Panel.

---

## 🧪 Test nhanh

```bash
# Test thủ công
php artisan bookings:check-alerts

# Kiểm tra scheduler
php artisan schedule:list

# Xem số lượng notifications
php artisan tinker --execute="echo DB::table('notifications')->count();"

# Reset để test lại
php artisan tinker --execute="DB::table('bookings')->update(['is_reminded_upcoming' => false, 'is_reminded_late' => false]);"
```

---

## 📚 Tài liệu chi tiết

- 📖 **Hướng dẫn đầy đủ:** `HUONG-DAN-SCHEDULER.md`
- 📝 **Tóm tắt thay đổi:** `TOM-TAT-THAY-DOI.md`

---

## ⚙️ Cấu hình

File: `routes/console.php`

```php
Schedule::command('bookings:check-alerts')
    ->everyMinute()        // Đổi thành everyFiveMinutes() cho production
    ->withoutOverlapping()
    ->runInBackground();
```

---

## ✅ Checklist

- [x] Notifications hiển thị đúng trên Filament
- [x] Scheduler tự động chạy
- [x] Format đúng chuẩn Filament
- [x] Có icon, màu sắc phù hợp
- [x] Không gửi trùng lặp

---

**Lưu ý:** Giữ cửa sổ scheduler mở khi đang làm việc! 🚀

