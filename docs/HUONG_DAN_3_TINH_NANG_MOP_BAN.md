# 📋 Hướng Dẫn: 3 Tính Năng Quản Lý Bàn Billiard Mới

## 🎯 Tổng Quan

Hệ thống quản lý bàn billiard đã được nâng cấp với 3 tính năng quan trọng:

1. **⏸️ Tạm Dừng Thời Gian** - Dừng tính giờ khi bàn không sử dụng
2. **🔗 Ghép Hóa Đơn** - Kết hợp hóa đơn từ nhiều bàn
3. **🔄 Đổi Bàn** - Chuyển bàn khi gặp sự cố

---

## 1️⃣ TÍNH NĂNG: ⏸️ TẠM DỪNG THỜI GIAN

### Mục đích
Khi người chơi dừng lại (không chơi nữa) nhưng còn ngồi nói chuyện hoặc uống nước, bàn vẫn đang bị tính giờ. Tính năng này cho phép bạn tạm dừng, khi nào người chơi tiếp tục thì tiếp tục lại.

### Cách sử dụng

#### **Trên danh sách lịch sử:**
1. Mở mục **"Lịch sử"** trong menu
2. Nhìn danh sách các phiên đang chơi (status = "🔴 Đang chơi")
3. Nhấn nút **"⏸️ Tạm dừng"** (hoặc **"▶️ Tiếp tục"** nếu đã tạm dừng)

#### **Trên chi tiết hóa đơn:**
1. Nhấn nút **"Chi tiết"** của một phiên đang chơi
2. Bên trên, sẽ có nút **"⏸️ Tạm dừng"** hoặc **"▶️ Tiếp tục"**
3. Nhấn để bật/tắt tạm dừng

### Dữ liệu lưu trữ
- **paused_at**: Thời điểm bắt đầu tạm dừng (NULL = đang chạy)
- **total_paused_seconds**: Tổng thời gian đã tạm dừng (tính bằng giây)
- Khi tính tiền, hệ thống **tự động trừ đi** thời gian tạm dừng

### Ví dụ
- Bàn 4 chơi từ 14:00, tạm dừng lúc 14:30 (đã chơi 30 phút)
- Người chơi ngồi nói chuyện 20 phút
- Tiếp tục chơi lúc 14:50
- Tổng thời gian thực tế = 50 phút - 20 phút tạm dừng = 30 phút
- Tiền sẽ được tính cho 30 phút, không phải 50 phút

---

## 2️⃣ TÍNH NĂNG: 🔗 GHÉP HÓA ĐƠN

### Mục đích
Khi một khách hàng thanh toán cho 2-3 bàn cùng lúc, thay vì phải cộng thủ công hay tạo hóa đơn mới, bạn có thể ghép hóa đơn của các bàn lại với nhau thành 1 hóa đơn duy nhất.

### Cách sử dụng

#### **Bước 1: Mở hóa đơn bàn muốn ghép**
1. Vào **"Lịch sử"**
2. Tìm phiên chơi bàn 1 (vd: Bàn 4)
3. Nhấn **"Chi tiết"** để xem chi tiết

#### **Bước 2: Ghép vào bàn khác**
1. Trong trang chi tiết, nhấn nút **"🔗 Ghép hóa đơn"**
2. Một modal popup sẽ hiện lên
3. Chọn **"Hóa đơn để ghép vào"** từ dropdown (vd: #1234 - Bàn 3)
4. Nhấn **"Ghép"**

#### **Kết quả:**
- Tất cả **món ăn** của bàn 4 sẽ được **chuyển** vào hóa đơn bàn 3
- Hóa đơn bàn 4 sẽ bị **đánh dấu là "🔗 Đã ghép"** (không thể thanh toán riêng)
- Hóa đơn bàn 3 sẽ chứa **tất cả món** của cả 2 bàn
- Khi thanh toán bàn 3, sẽ có tổng tiền của cả 2 bàn

### Ví dụ thực tế

**Trước:**
```
Bàn 3:
- 2 bia Heineken: 60,000đ
- 1 xúc xích: 35,000đ
- Tổng: 95,000đ

Bàn 4:
- 3 nước cam: 30,000đ
- 2 cơm chiên: 80,000đ
- Tổng: 110,000đ
```

**Sau ghép (ghép bàn 4 vào bàn 3):**
```
Hóa đơn #1234 (Bàn 3 + Bàn 4):
- 2 bia Heineken: 60,000đ
- 1 xúc xích: 35,000đ
- 3 nước cam: 30,000đ
- 2 cơm chiên: 80,000đ
- Tổng: 205,000đ

Hóa đơn bàn 4: ❌ "🔗 Đã ghép"
```

### Dữ liệu lưu trữ
- **merged_into_session_id**: ID của hóa đơn mà phiên này được ghép vào
- **status**: Sẽ đổi từ "running" thành "merged"
- Các order items sẽ được cập nhật `game_session_id` sang phiên mới

---

## 3️⃣ TÍNH NĂNG: 🔄 ĐỔI BÀN

### Mục đích
Khi một bàn bi-a bị hỏng (quạt không chạy, bàn hỏng, etc.), khách muốn chuyển sang bàn khác. Tính năng này tự động:
1. **Lưu lại** hóa đơn trên bàn cũ (với thời gian và các món đã gọi)
2. **Tạo phiên mới** trên bàn mới (tiếp tục chơi từ đó)
3. **Liên kết** cả 2 phiên lại (để biết phiên này đổi từ đâu)

### Cách sử dụng

#### **Bước 1: Mở hóa đơn bàn cũ**
1. Vào **"Lịch sử"**
2. Tìm phiên chơi bàn cũ
3. Nhấn **"Chi tiết"**

#### **Bước 2: Chọn bàn mới**
1. Nhấn nút **"🔄 Đổi bàn"**
2. Modal popup hiện lên
3. Chọn **"Bàn mới"** từ dropdown
4. (Tùy chọn) Nhập **"Lý do đổi bàn"** (vd: "Bàn cũ quạt hỏng")
5. Nhấn **"Đổi bàn"**

#### **Kết quả:**
- **Hóa đơn bàn cũ** sẽ bị đóng (status = "🔄 Đã đổi bàn")
  - Thời gian chơi + các món gọi vẫn giữ nguyên
  - Sẽ được thanh toán sau
- **Phiên chơi mới** được tạo trên bàn mới
  - Bắt đầu từ thời điểm hiện tại
  - Có liên kết đến phiên cũ
  - Khách tiếp tục chơi, gọi thêm số nước...
- Cuối cùng, **ghép 2 hóa đơn** lại và thanh toán chung

### Ví dụ thực tế

**Ban đầu:**
```
Bàn 4: 14:00 - Đang chơi
- Giờ chơi: 30 phút
- 2 nước ngọt: 20,000đ
```

**Lúc 14:30, bàn bị hỏng → Đổi sang bàn 3:**

```
Hóa đơn bàn 4 (Cũ): 🔄 Đã đổi bàn
- Giờ chơi: 30 phút
- 2 nước ngọt: 20,000đ
- Tổng: ~150,000đ (phụ thuộc giá giờ)

Phiên bàn 3 (Mới): ▶️ Đang chơi
- Bắt đầu: 14:30
- Đổi từ: Bàn 4
- Lý do: "Bàn quạt hỏng"
```

**Khi khách thanh toán (14:50):**
1. Tính tiền bàn 3: 20 phút giờ chơi + thêm nước mới
2. **Ghép** hóa đơn bàn 4 + bàn 3
3. Tổng tiền = hóa đơn bàn cũ + hóa đơn bàn mới

### Dữ liệu lưu trữ
- **transferred_from_session_id**: ID của phiên cũ
- **transfer_reason**: Lý do đổi bàn
- **status**: Phiên cũ = "transferred", phiên mới = "running"

---

## ⚙️ TÍCH HỢP VỚI HỆ THỐNG TRẢ TIỀN

Khi ghép hóa đơn, hệ thống sẽ:
1. **Cộng tất cả các món ăn** từ các bàn
2. **Cộng tiền giờ** của từng bàn (đã trừ thời gian tạm dừng)
3. **Tính VAT** riêng cho từng loại bàn
4. **Tính tổng tiền** cuối cùng

**Ví dụ thanh toán ghép:**
```
Bàn 3 (30 phút): 
- Giờ chơi: ~130,000đ
- Bia x2: 60,000đ
- Tổng: 190,000đ

Bàn 4 (30 phút):
- Giờ chơi: ~130,000đ
- Nước: 30,000đ
- Tổng: 160,000đ

HÓNG CHUNG (đã ghép):
- Giờ chơi 2 bàn: 260,000đ
- Thức ăn/thức uống: 90,000đ
- Tổng tiền hàng: 350,000đ
- VAT (10%): 35,000đ
- TỔNG THANH TOÁN: 385,000đ ✓
```

---

## 📊 THÔNG TIN KỸ THUẬT

### Database Migrations
- ✅ `2026_02_06_010000_add_pause_columns_to_game_sessions`
  - Thêm: `paused_at`, `total_paused_seconds`

- ✅ `2026_02_06_150000_add_merge_transfer_columns`
  - Thêm: `merged_into_session_id`, `transferred_from_session_id`, `transfer_reason`

### Models
- **GameSession**
  - Methods: `pause()`, `resume()`, `isPaused()`, `isMerged()`, `isTransferred()`
  - Relations: `mergedSessions()`, `mergedIntoSession()`, `transferredFromSession()`, `transferredSession()`

### Service
- **TableManagementService**
  - `mergeSession($source, $target)`
  - `transferTableSession($oldSession, $newTableId, $reason)`
  - `pauseSession($session)`
  - `resumeSession($session)`
  - `endSession($session)`

### UI Updates
- ✅ Danh sách (Table): Thêm action "Tạm dừng/Tiếp tục"
- ✅ Chi tiết (Form): Thêm action "Ghép", "Đổi bàn", "Tạm dừng"
- ✅ Hiển thị status: "⏸️ Đang tạm dừng", "🔗 Đã ghép", "🔄 Đã đổi bàn"

---

## 🐛 TROUBLESHOOT

### Q: Tại sao tạm dừng nhưng hóa đơn vẫn tính giờ?
**A:** Nó không tính! Thời gian tạm dừng được **trừ ra** khi thanh toán. Nếu thấy sai, hãy check `total_paused_seconds` trong database.

### Q: Ghép hóa đơn xong, bàn cũ có thể thanh toán riêng không?
**A:** Không, hóa đơn bàn cũ sẽ bị khóa (status = "merged"). Phải thanh toán hóa đơn bàn mới (đã chứa cả 2 bàn).

### Q: Đổi bàn xong, tiền giờ bàn cũ tính sao?
**A:** Tiền giờ bàn cũ tính đến lúc đổi bàn. Bàn mới tính từ lúc đổi. Lúc thanh toán ghép hóa đơn, cả 2 cộng lại.

### Q: Có thể đổi lại bàn không?
**A:** Có, tạo phiên mới khác trên bàn cũ, rồi ghép lại.

---

## 📞 HỖ TRỢ

Nếu gặp vấn đề:
1. Kiểm tra logs: `storage/logs/laravel.log`
2. Xem database: các cột `paused_at`, `merged_into_session_id`, `transferred_from_session_id`
3. Kiểm tra migrations đã chạy đủ chưa: `php artisan migrate:status`

---

**Cập nhật: 06/02/2026** ✨
