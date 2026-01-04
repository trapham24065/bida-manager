# 🔔 Hướng dẫn chạy tự động thông báo đặt bàn

## 📋 Tổng quan

Hệ thống sẽ **tự động kiểm tra** và gửi thông báo cho admin/staff khi:
- ⏰ **Booking sắp tới** (trong vòng 15 phút)
- ⚠️ **Booking đã trễ** (quá giờ đặt)

Thông báo sẽ hiển thị trên **icon chuông 🔔** ở góc trên bên phải trong Filament Admin Panel.

---

## 🚀 Cách chạy trên LOCAL (Windows + WAMP)

### **Cách 1: Chạy file PowerShell (Khuyên dùng)** ⭐

1. **Click phải** vào file `run-scheduler.ps1`
2. Chọn **"Run with PowerShell"**
3. Một cửa sổ PowerShell sẽ mở ra và chạy scheduler mỗi 60 giây
4. **Giữ cửa sổ này mở** khi đang làm việc
5. Nhấn `Ctrl+C` để dừng khi không cần nữa

**Hoặc** mở PowerShell trong thư mục project và chạy:

```powershell
.\run-scheduler.ps1
```

**Lưu ý:** Scheduler sẽ chạy lệnh `bookings:check-alerts` mỗi phút (theo cấu hình trong `routes/console.php`)

---

### **Cách 2: Chạy file BAT**

1. **Double-click** vào file `run-scheduler.bat`
2. Một cửa sổ CMD sẽ mở ra và chạy scheduler mỗi 60 giây
3. **Giữ cửa sổ này mở** khi đang làm việc
4. Nhấn `Ctrl+C` để dừng khi không cần nữa

---

### **Cách 3: Chạy bằng CMD**

Mở CMD trong thư mục project và chạy:

```cmd
php artisan schedule:run
```

**Lưu ý:** Lệnh này chỉ chạy 1 lần. Bạn cần chạy lại mỗi phút hoặc dùng Cách 1.

---

## ⚙️ Cấu hình Scheduler

File: `routes/console.php`

```php
Schedule::command('bookings:check-alerts')
    ->everyFiveMinutes()      // Chạy mỗi 5 phút
    ->withoutOverlapping()    // Tránh chạy trùng lặp
    ->runInBackground();      // Chạy background
```

### Các tùy chọn thời gian:

- `->everyMinute()` - Mỗi phút ⭐ (Đang dùng - tốt cho test)
- `->everyFiveMinutes()` - Mỗi 5 phút (Khuyên dùng cho production)
- `->everyTenMinutes()` - Mỗi 10 phút
- `->everyFifteenMinutes()` - Mỗi 15 phút
- `->hourly()` - Mỗi giờ

**Lưu ý:** Hiện tại đang dùng `everyMinute()` để dễ test. Khi deploy lên production, nên đổi thành `everyFiveMinutes()` để giảm tải server.

---

## 🧪 Test thủ công

Nếu muốn test ngay lập tức mà không chờ scheduler:

```bash
php artisan bookings:check-alerts
```

---

## 🖥️ Triển khai lên SERVER (Production)

### **Linux/Ubuntu:**

Thêm vào crontab:

```bash
crontab -e
```

Thêm dòng này:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### **Windows Server:**

Tạo **Task Scheduler**:
1. Mở `Task Scheduler`
2. Tạo task mới chạy mỗi phút
3. Action: `php.exe artisan schedule:run`
4. Start in: `C:\path\to\project`

---

## 📊 Kiểm tra Scheduler đang chạy

```bash
php artisan schedule:list
```

Kết quả:

```
0 */5 * * *  php artisan bookings:check-alerts .... Next Due: 5 minutes from now
```

---

## 🐛 Troubleshooting

### Scheduler không chạy?

1. **Kiểm tra file `routes/console.php`** có đúng cấu hình không
2. **Chạy thủ công** để test: `php artisan bookings:check-alerts`
3. **Kiểm tra log**: `storage/logs/laravel.log`

### Notifications không hiển thị?

1. **Kiểm tra database** có notifications không:
   ```bash
   php artisan tinker --execute="echo DB::table('notifications')->count();"
   ```

2. **Kiểm tra format** của notification (phải có `format: "filament"`)

3. **Clear cache**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

4. **Refresh trang admin** và kiểm tra icon chuông 🔔

---

## 📝 Ghi chú

- Scheduler chỉ gửi notification **1 lần** cho mỗi booking (nhờ có flag `is_reminded_upcoming` và `is_reminded_late`)
- Notifications sẽ hiển thị cho **tất cả admin và staff**
- Notifications có thể **đánh dấu đã đọc** bằng cách click vào
- Notifications **không tự xóa**, bạn cần xóa thủ công nếu muốn

---

## 🎯 Tóm tắt

✅ **Đã cấu hình:** Scheduler chạy mỗi 5 phút
✅ **Đã tạo:** File `run-scheduler.bat` để chạy trên local
✅ **Đã sửa:** Notification format để hiển thị đúng trên Filament

**Để bắt đầu:** Double-click vào `run-scheduler.bat` và giữ cửa sổ mở! 🚀

