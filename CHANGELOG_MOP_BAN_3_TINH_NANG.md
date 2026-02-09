# 🔧 CHANGELOG: Nâng Cấp Quản Lý Bàn Billiard

**Phiên bản:** 1.0 (06/02/2026)

---

## 📦 Files Tạo Mới

### 1. Migration Files
```
database/migrations/2026_02_06_010000_add_pause_columns_to_game_sessions.php
database/migrations/2026_02_06_150000_add_merge_transfer_columns.php
```

### 2. Service Files
```
app/Services/TableManagementService.php
```

### 3. Documentation
```
docs/HUONG_DAN_3_TINH_NANG_MOP_BAN.md
```

---

## ✏️ Files Sửa Đổi

### 1. Models

#### `app/Models/GameSession.php`
**Thêm:**
- Relations:
  - `mergedSessions()` - Các phiên được ghép vào phiên này
  - `mergedIntoSession()` - Phiên mà phiên này được ghép vào
  - `transferredFromSession()` - Phiên mà phiên này được chuyển từ
  - `transferredSession()` - Phiên được chuyển từ phiên này

- Methods:
  - `isMerged()` - Kiểm tra phiên có bị ghép không
  - `isTransferred()` - Kiểm tra phiên có bị chuyển bàn không
  - `getAllMergedSessions()` - Lấy tất cả phiên được ghép (đệ quy)
  - `getTotalMoneyIncludingMerged()` - Tính tổng tiền bao gồm cả phiên ghép
  - `getAllOrderItems()` - Lấy tất cả món từ phiên ghép

---

### 2. Filament Resources

#### `app/Filament/Resources/GameSessions/GameSessionResource.php`
**Sửa:**
- Thêm `use Illuminate\Support\Facades\Auth;` để tránh lỗi IDE
- Cập nhật logic auth check: `Auth::user()` thay vì `auth()->user()`

---

#### `app/Filament/Resources/GameSessions/Tables/GameSessionsTable.php`
**Thêm:**
- Imports:
  - `Filament\Actions\Action`
  - `App\Services\TableManagementService`
  - `Filament\Notifications\Notification`

- Columns:
  - `paused_at` (TextColumn) - Hiển thị "⏸️ Đang tạm dừng" hoặc "▶️ Đang chạy"
  - Cập nhật `status` column với format icons và màu mới:
    - "🔴 Đang chơi" (warning)
    - "✅ Thanh toán rồi" (success)
    - "🔄 Đã đổi bàn" (info)
    - "🔗 Đã ghép" (gray)

- Table Actions:
  - `togglePause()` - Tạm dừng/Tiếp tục bàn
    - Hiển thị: chỉ cho phiên running
    - Gọi: `TableManagementService::pauseSession()` hoặc `resumeSession()`
    - Notification: Thông báo thành công

---

#### `app/Filament/Resources/GameSessions/Schemas/GameSessionForm.php`
**Sửa:**
- Import: `Filament\Schemas\Components\Section` (không phải `Filament\Forms\Components\Section`)
- Import: `Filament\Schemas\Schema` (không phải `Filament\Forms\Schema`)

- Thêm TextInput fields (để hiển thị info):
  - `pause_status` - Hiển thị trạng thái tạm dừng (ẩn nếu không running)
  - `merge_status` - Hiển thị hóa đơn bị ghép vào (ẩn nếu không merged)
  - `transfer_status` - Hiển thị hóa đơn được chuyển từ (ẩn nếu không transferred)

---

#### `app/Filament/Resources/GameSessions/Pages/EditGameSession.php`
**Thêm:**
- Imports:
  - `Filament\Actions\Action`
  - `App\Services\TableManagementService`
  - `Filament\Notifications\Notification`
  - `Filament\Forms\Components\Select`
  - `Filament\Forms\Components\Textarea`
  - `App\Models\GameSession`
  - `App\Models\Table`

- Header Actions:
  - `merge()` - Ghép hóa đơn
    - Form: Select hóa đơn đích
    - Logic: Gọi `TableManagementService::mergeSession()`
    - Validation: Chỉ cho phiên running
  
  - `transferTable()` - Đổi bàn
    - Form: Select bàn mới + Textarea lý do
    - Logic: Gọi `TableManagementService::transferTableSession()`
    - Validation: Chỉ cho phiên running
  
  - `togglePause()` - Tạm dừng/Tiếp tục
    - Form: Không cần
    - Logic: Gọi `TableManagementService::pauseSession()` hoặc `resumeSession()`
    - Confirmation: Yêu cầu xác nhận

---

### 3. Services

#### `app/Services/TableManagementService.php` (TẠO MỚI)
**Public Methods:**

1. **mergeSession(GameSession $source, GameSession $target)**
   - Chuyển tất cả orderItems từ source sang target
   - Cập nhật source: `merged_into_session_id`, `status = 'merged'`
   - Exception: Nếu phiên không running hoặc merge chính nó

2. **transferTableSession(GameSession $oldSession, int $newTableId, string $reason)**
   - Tạo GameSession mới trên bàn mới
   - Cập nhật oldSession: `status = 'transferred'`
   - Link: `transferred_from_session_id`, `transfer_reason`
   - Return: Phiên mới

3. **pauseSession(GameSession $session)**
   - Gọi `$session->pause()`
   - Exception: Nếu phiên không running

4. **resumeSession(GameSession $session)**
   - Gọi `$session->resume()`
   - Exception: Nếu phiên không running hoặc chưa bị pause

5. **endSession(GameSession $session)**
   - Cập nhật: `end_time`, `status = 'completed'`, `paused_at = null`
   - Exception: Nếu phiên đã kết thúc

6. **getSessionDetails(GameSession $session)**
   - Return array: main_session, merged_sessions, all_sessions, all_order_items, total_items_count, total_money
   - Sử dụng cho invoice/report

7. **getRunningSessionsWithPauseInfo()**
   - Return: Collection các phiên running với info pause
   - Sử dụng cho dashboard/real-time display

---

## 🗄️ Database Changes

### game_sessions Table

**Cột mới từ Migration #1:**
```sql
ALTER TABLE game_sessions ADD COLUMN paused_at TIMESTAMP NULL;
ALTER TABLE game_sessions ADD COLUMN total_paused_seconds INT DEFAULT 0;
```

**Cột mới từ Migration #2:**
```sql
ALTER TABLE game_sessions ADD COLUMN merged_into_session_id BIGINT UNSIGNED NULL;
ALTER TABLE game_sessions ADD COLUMN transferred_from_session_id BIGINT UNSIGNED NULL;
ALTER TABLE game_sessions ADD COLUMN transfer_reason VARCHAR(255) NULL;

ALTER TABLE game_sessions 
ADD FOREIGN KEY (merged_into_session_id) 
REFERENCES game_sessions(id) ON DELETE SET NULL;

ALTER TABLE game_sessions 
ADD FOREIGN KEY (transferred_from_session_id) 
REFERENCES game_sessions(id) ON DELETE SET NULL;
```

**Cột Status mở rộng:**
- Giá trị cũ: 'running', 'completed'
- Giá trị mới: 'running', 'completed', 'merged', 'transferred'

---

## 🔍 Logic Flow

### Pause/Resume Flow
```
User nhấn "⏸️ Tạm dừng"
    ↓
GameSession::pause()
    ↓
$session->paused_at = now()
$session->save()
    ↓
UI cập nhật: "⏸️ Đang tạm dừng"

---

User nhấn "▶️ Tiếp tục"
    ↓
GameSession::resume()
    ↓
Tính: pausedSeconds = now() - paused_at
total_paused_seconds += pausedSeconds
paused_at = null
$session->save()
    ↓
UI cập nhật: "▶️ Đang chạy"
```

### Merge Flow
```
User chọn "🔗 Ghép hóa đơn"
    ↓
Select hóa đơn đích (ví dụ: #1234)
    ↓
TableManagementService::mergeSession(#5678, #1234)
    ↓
UPDATE order_items SET game_session_id = 1234 
WHERE game_session_id = 5678
    ↓
UPDATE game_sessions SET merged_into_session_id = 1234, 
status = 'merged' WHERE id = 5678
    ↓
#5678 status = "🔗 Đã ghép" (không thể thanh toán riêng)
#1234 status = "🔴 Đang chơi" (chứa cả 2 bàn)
```

### Transfer Flow
```
User chọn "🔄 Đổi bàn"
    ↓
Select bàn mới + nhập lý do
    ↓
TableManagementService::transferTableSession(oldSession, newTableId)
    ↓
INSERT INTO game_sessions (table_id, transferred_from_session_id, ...)
    ↓
UPDATE game_sessions SET status = 'transferred' WHERE id = oldSession
    ↓
oldSession: status = "🔄 Đã đổi bàn" (hoàn tất, được thanh toán)
newSession: status = "🔴 Đang chơi" (tiếp tục)

Sau đó, user có thể ghép 2 hóa đơn lại
```

---

## 🧪 Testing Checklist

- [ ] Migration chạy không lỗi
- [ ] Pause/Resume button hiển thị đúng
- [ ] Pause/Resume cập nhật database đúng
- [ ] Tiền giờ trừ đi thời gian pause khi thanh toán
- [ ] Ghép hóa đơn: orderItems chuyển qua đúng
- [ ] Ghép hóa đơn: status cập nhật thành "merged"
- [ ] Đổi bàn: Phiên mới được tạo đúng
- [ ] Đổi bàn: Phiên cũ status = "transferred"
- [ ] Notification thông báo thành công
- [ ] Ghép + Thanh toán: Tính toán tiền đúng
- [ ] UI hiển thị icons và màu đúng

---

## 📌 Notes

1. **Pause logic**: Thời gian tạm dừng được **trừ đi** trong `BillingService::calculateTimeFee()` thông qua phương thức `getActualPlayingMinutes()`

2. **Merge logic**: Chỉ chuyển **orderItems**, không chuyển **start_time/end_time**. Nên 2 phiên sẽ có thời gian riêng, nhưng chung **hóa đơn cuối cùng**

3. **Transfer logic**: Tạo phiên **hoàn toàn mới** trên bàn mới, giữ lại **dữ liệu phiên cũ** để thanh toán riêng (nếu cần)

4. **Status options**: Có 4 trạng thái nay:
   - **running**: Đang chơi (có thể pause/resume/merge/transfer)
   - **completed**: Đã thanh toán
   - **merged**: Đã ghép vào hóa đơn khác (không thể thanh toán riêng)
   - **transferred**: Đã chuyển bàn (đóng, chờ thanh toán hoặc ghép)

---

**Created:** 06/02/2026  
**Author:** GitHub Copilot  
**Status:** ✅ Ready for Testing
