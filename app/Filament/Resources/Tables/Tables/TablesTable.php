<?php

namespace App\Filament\Resources\Tables\Tables;

use App\Models\Customer;
use App\Models\CustomerRank;
use App\Models\GameSession;
use App\Models\OrderItem;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Models\Table as TableModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;

// Import Action chuẩn
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

// Import Grid của Form
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class TablesTable
{

    public static function configure(Table $table): Table
    {
        return $table
            // 1. CẤU HÌNH LƯỚI
            ->contentGrid([
                'md'  => 2,
                'xl'  => 3,
                '2xl' => 4,
            ])

            // 2. GIAO DIỆN CARD
            ->columns([
                View::make('filament.tables.columns.bida-card'),
            ])

            // 3. CẤU HÌNH ACTION
            ->recordActions([
                // === ACTION: BẮT ĐẦU ===
                Action::make('start')
                    ->label('Bắt đầu')
                    ->button()
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn(TableModel $record) => !$record->hasRunningSession())
                    ->requiresConfirmation()
                    ->action(fn(TableModel $record) => self::startSession($record)),

                // === ACTION: GỌI MÓN (BEST SELLER) ===
                Action::make('order')
                    ->label('Gọi món')
                    ->button()
                    ->icon('heroicon-o-shopping-cart')
                    ->color('warning')
                    ->visible(fn(TableModel $record) => $record->hasRunningSession())
                    ->modalHeading('Gọi món')
                    ->modalWidth('lg')
                    ->form([
                        Repeater::make('items')
                            ->label('Danh sách món')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Món')
                                    ->options(function () {
                                        $topProductIds = \App\Models\OrderItem::select(
                                            'product_id',
                                            DB::raw('SUM(quantity) as total')
                                        )
                                            ->groupBy('product_id')
                                            ->orderByDesc('total')
                                            ->limit(5)
                                            ->pluck('product_id')
                                            ->toArray();

                                        return Product::where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(function ($product) use ($topProductIds) {
                                                $imgUrl = $product->image
                                                    ? \Illuminate\Support\Facades\Storage::disk('public')->url(
                                                        $product->image
                                                    )
                                                    : 'https://placehold.co/50x50?text=No+Img';

                                                $badge = '';
                                                if (in_array($product->id, $topProductIds)) {
                                                    $badge
                                                        = "<span style='background: #ef4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 99px; font-weight: bold; margin-left: 5px;'>🔥 HOT</span>";
                                                }

                                                $html = "
                                                    <div class='flex items-center gap-2'>
                                                        <div style='position: relative;'>
                                                            <img src='{$imgUrl}' style='width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;'>
                                                        </div>
                                                        <div>
                                                            <div class='font-bold text-sm'>{$product->name} {$badge}</div>
                                                            <div class='text-xs text-gray-500'>".number_format(
                                                        $product->price
                                                    )." đ</div>
                                                        </div>
                                                    </div>";
                                                return [$product->id => $html];
                                            });
                                    })
                                    ->required()
                                    ->searchable()
                                    ->allowHtml(),

                                TextInput::make('quantity')
                                    ->label('Số lượng')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('➕ Thêm món'),
                    ])
                    ->action(function (TableModel $record, array $data) {
                        self::orderItems($record, $data);
                    }),

                // === ACTION: TÍNH TIỀN (LOGIC MỚI) ===
                Action::make('stop')
                    ->label('Tính tiền')
                    ->button()
                    ->icon('heroicon-o-printer')
                    ->color('danger')
                    ->visible(fn(TableModel $record) => $record->hasRunningSession())
                    ->modalHeading('Xác nhận thanh toán')
                    ->modalDescription('Kiểm tra kỹ hóa đơn và chọn khách hàng để áp dụng ưu đãi')
                    ->modalSubmitActionLabel('✅ Thanh toán & In hóa đơn')
                    ->modalWidth('lg')
                    ->form([
                        // 1. Xem trước hóa đơn
                        Placeholder::make('bill_preview')
                            ->label('Tạm tính')
                            ->content(fn(TableModel $record) => self::previewBill($record)),

                        // 2. Chọn khách hàng (CÓ LOGIC TỰ ĐỘNG GIẢM GIÁ)
                        Select::make('customer_id')
                            ->label('Khách hàng thành viên')
                            ->options(\App\Models\Customer::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required()->label('Tên'),
                                TextInput::make('phone')->required()->unique('customers')->label('SĐT'),
                            ])
                            ->createOptionUsing(fn(array $data) => \App\Models\Customer::create($data)->id)
                            ->placeholder('Chọn khách hoặc để trống nếu khách vãng lai')

                            // === SỬA DÒNG DƯỚI ĐÂY ===
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (!$state) {
                                    $set('discount_percent', 0);
                                    return;
                                }

                                $customer = \App\Models\Customer::with('rank')->find($state);

                                if ($customer && $customer->rank) {
                                    $discount = $customer->rank->discount_percent;
                                    $set('discount_percent', $discount);

                                    if ($discount > 0) {
                                        Notification::make()
                                            ->title("Khách hạng: {$customer->rank->name}")
                                            ->body("Đã tự động áp dụng giảm {$discount}%")
                                            ->success()
                                            ->send();
                                    }
                                } else {
                                    $set('discount_percent', 0);
                                }
                            }),
                        Select::make('payment_method')
                            ->label('Hình thức thanh toán')
                            ->options([
                                'cash'     => 'Tiền mặt',
                                'transfer' => 'Chuyển khoản / QR',
                            ])
                            ->default('cash')
                            ->required()
                            ->native(false), // Giao diện đẹp hơn
                        // 3. Form Giảm giá
                        Section::make('Ưu đãi / Giảm giá')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('discount_percent')
                                        ->label('Giảm theo %')
                                        ->numeric()
                                        ->minValue(0)->maxValue(100)
                                        ->default(0)
                                        ->suffix('%')
                                        ->live()
                                        ->afterStateUpdated(fn($set) => $set('discount_amount', 0)),

                                    TextInput::make('discount_amount')
                                        ->label('Giảm tiền mặt')
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('VNĐ')
                                        ->live()
                                        ->afterStateUpdated(fn($set) => $set('discount_percent', 0)),
                                ]),

                                Textarea::make('note')
                                    ->label('Lý do giảm / Ghi chú')
                                    ->placeholder('VD: Khách quen, Khai trương...'),
                            ]),
                    ])
                    // === LOGIC TÍNH TIỀN MỚI Ở ĐÂY ===
                    ->action(
                        function (
                            TableModel $record,
                            array $data,
                            Action $action
                        ) {
                            // === 1. KIỂM TRA CA LÀM VIỆC ===
                            $currentShift = \App\Models\WorkShift::myCurrentShift();
                            if (!$currentShift) {
                                Notification::make()
                                    ->title('Chưa mở ca làm việc!')
                                    ->body('Bạn phải "Vào Ca" trước khi thực hiện thanh toán.')
                                    ->danger()
                                    ->actions([
                                        Action::make('open_shift')
                                            ->label('Đi mở ca ngay')
                                            ->url('/admin/work-shifts')
                                            ->button(),
                                    ])
                                    ->persistent()
                                    ->send();

                                $action->halt();
                                return;
                            }

                            $session = $record->currentSession;
                            if (!$session) {
                                Notification::make()->title('Lỗi phiên chơi')->danger()->send();
                                return;
                            }

                            // 2. TÍNH TỔNG TIỀN GỐC (SUBTOTAL)
                            $timeMoney = self::calculateTimeMoney($record, $session);
                            $serviceMoney = $session->orderItems->sum('total');
                            $subTotal = $timeMoney + $serviceMoney;

                            // 3. XỬ LÝ GIẢM GIÁ
                            $discount = 0;
                            if ($data['discount_percent'] > 0) {
                                $discount = ($subTotal * $data['discount_percent']) / 100;
                            } elseif ($data['discount_amount'] > 0) {
                                $discount = $data['discount_amount'];
                            }

                            // Validate giảm giá
                            if ($discount > $subTotal) {
                                Notification::make()
                                    ->title('Giảm giá không hợp lệ!')
                                    ->body(
                                        'Số tiền giảm ('.number_format($discount).') lớn hơn tổng tiền ('.number_format(
                                            $subTotal
                                        ).').'
                                    )
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                $action->halt();
                                return;
                            }

                            // Tính tổng tiền sơ bộ (chưa làm tròn)
                            $finalTotal = $subTotal - $discount;

                            // ============================================================
                            // === 4. LOGIC LÀM TRÒN TIỀN THÔNG MINH (MỚI THÊM) ===
                            // ============================================================

                            // Lấy cấu hình từ ShopSetting
                            $setting = ShopSetting::first();
                            $roundingMode = $setting?->rounding_mode ?? 'none';
                            $roundingDiff = 0; // Biến lưu số tiền chênh lệch

                            // Chỉ làm tròn khi tiền > 0 và có bật chế độ làm tròn
                            if ($finalTotal > 0 && $roundingMode !== 'none') {
                                $originalTotal = $finalTotal;

                                switch ($roundingMode) {
                                    case 'down': // Luôn làm tròn XUỐNG (43.900 -> 43.000)
                                        $finalTotal = floor($originalTotal / 1000) * 1000;
                                        break;

                                    case 'up': // Luôn làm tròn LÊN (43.100 -> 44.000)
                                        $finalTotal = ceil($originalTotal / 1000) * 1000;
                                        break;

                                    case 'auto': // Tự động (>=500 lên, <500 xuống)
                                        $finalTotal = round($originalTotal / 1000) * 1000;
                                        break;
                                }

                                $roundingDiff = $finalTotal - $originalTotal;
                            }
                            // ============================================================

                            // 5. LƯU DỮ LIỆU (Cập nhật cả rounding_amount)
                            $session->update([
                                'end_time'         => now(),
                                'total_money'      => $finalTotal,
                                'rounding_amount'  => $roundingDiff,
                                'payment_method'   => $data['payment_method'],
                                'discount_percent' => $data['discount_percent'],
                                'discount_amount'  => $data['discount_amount'],
                                'note'             => $data['note'],
                                'status'           => 'completed',
                                'customer_id'      => $data['customer_id'],
                                'work_shift_id'    => $currentShift->id,
                            ]);

                            // 6. CỘNG ĐIỂM & XẾP HẠNG (Dùng finalTotal đã làm tròn để tính điểm)
                            if ($data['customer_id']) {
                                $customer = Customer::find($data['customer_id']);
                                if ($customer) {
                                    $customer->total_spending += $finalTotal;
                                    $pointsEarned = floor($finalTotal / 100000); // 100k = 1 điểm
                                    $customer->points += $pointsEarned;

                                    // Check lên hạng
                                    $newRank = CustomerRank::where(
                                        'min_spending',
                                        '<=',
                                        $customer->total_spending
                                    )
                                        ->orderByDesc('min_spending')
                                        ->first();

                                    if ($newRank && $customer->customer_rank_id !== $newRank->id) {
                                        $customer->customer_rank_id = $newRank->id;
                                        Notification::make()
                                            ->title("🎉 KHÁCH LÊN HẠNG!")
                                            ->body("{$customer->name} đã đạt hạng: {$newRank->name}")
                                            ->success()
                                            ->persistent()
                                            ->send();
                                    }
                                    $customer->save();

                                    Notification::make()
                                        ->title("Đã cộng {$pointsEarned} điểm cho {$customer->name}!")
                                        ->success()
                                        ->send();
                                }
                            }

                            Notification::make()->title('Thanh toán thành công!')->success()->send();

                            return redirect()->route('invoice.print', $session->id);
                        }
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /* =========================================================
     | BUSINESS LOGIC
     ========================================================= */

    protected static function startSession(TableModel $table): void
    {
        // LOGIC CHẶN MỞ BÀN NẾU CÓ LỊCH ĐẶT
        $upcomingBooking = \App\Models\Booking::where('table_id', $table->id)
            ->where('status', 'pending')
            ->whereBetween('booking_time', [
                now()->subMinutes(10), // Cho phép trễ 10p
                now()->addMinutes(60), // Chặn trước 60p
            ])
            ->first();

        if ($upcomingBooking) {
            $isLate = $upcomingBooking->booking_time->lessThan(now());
            $timeText = $upcomingBooking->booking_time->format('H:i');

            $msg = $isLate
                ? "Bàn này có khách đặt lúc {$timeText} (Đang trễ nhưng chưa hủy). Vui lòng check-in cho khách đặt!"
                : "Bàn này có khách đặt lúc {$timeText}. Không thể nhận khách vãng lai!";

            Notification::make()
                ->title('⛔ CẢNH BÁO TRÙNG LỊCH!')
                ->body($msg)
                ->danger()
                ->persistent()
                ->actions([
                    Action::make('view_booking')
                        ->label('Xử lý lịch đặt')
                        ->button()
                        ->url('/admin/bookings'),
                ])
                ->send();

            return;
        }

        GameSession::create([
            'table_id'   => $table->id,
            'start_time' => now(),
            'status'     => 'running',
        ]);
        Notification::make()->title('Đã mở bàn thành công!')->success()->send();
    }

    protected static function orderItems(TableModel $table, array $data): void
    {
        $session = $table->currentSession;
        if (!$session) {
            return;
        }

        $errors = [];

        foreach ($data['items'] as $item) {
            $product = Product::with('comboItems')->find($item['product_id']); // Load kèm comboItems
            if (!$product) {
                continue;
            }

            $orderQty = $item['quantity']; // Khách gọi bao nhiêu combo

            // === TRƯỜNG HỢP 1: LÀ COMBO ===
            if ($product->is_combo) {
                // 1. Kiểm tra đủ hàng không?
                foreach ($product->comboItems as $child) {
                    $neededQty = $child->pivot->quantity * $orderQty; // Cần: 5 bia * 2 combo = 10 bia
                    if ($child->stock < $neededQty) {
                        $errors[]
                            = "Không đủ hàng cho Combo: Món '{$child->name}' thiếu (Cần {$neededQty}, còn {$child->stock})";
                    }
                }

                // Nếu có lỗi thiếu hàng thì bỏ qua, không bán combo này
                if (count($errors) > 0) {
                    continue;
                }

                // 2. Nếu đủ hàng -> Trừ kho các món con
                foreach ($product->comboItems as $child) {
                    $deductQty = $child->pivot->quantity * $orderQty;
                    $child->decrement('stock', $deductQty);
                }
            } // === TRƯỜNG HỢP 2: LÀ MÓN THƯỜNG ===
            else {
                if ($product->stock < $orderQty) {
                    $errors[] = "Món '{$product->name}' chỉ còn {$product->stock}";
                    continue;
                }
                $product->decrement('stock', $orderQty);
            }

            // === TẠO ORDER ITEM (Lưu vào hóa đơn) ===
            // Dù là Combo hay Món thường thì vẫn lưu 1 dòng vào hóa đơn
            OrderItem::create([
                'game_session_id' => $session->id,
                'product_id'      => $product->id,
                'quantity'        => $orderQty,
                'price'           => $product->price,
                // Giá vốn của Combo = Tổng giá vốn các món con (Nếu muốn tính lãi chính xác)
                'cost'            => $product->is_combo
                    ? $product->comboItems->sum(fn($c) => $c->cost_price * $c->pivot->quantity)
                    : $product->cost_price,
                'total'           => $product->price * $orderQty,
            ]);
        }

        // Thông báo kết quả
        if (count($errors) > 0) {
            Notification::make()->title('Cảnh báo kho!')->body(implode("\n", $errors))->warning()->send();
        } else {
            Notification::make()->title('Đã lên món thành công!')->success()->send();
        }
    }

    protected static function previewBill(TableModel $table): HtmlString|string
    {
        $session = $table->currentSession;
        if (!$session) {
            return 'Không tìm thấy phiên chơi!';
        }

        $minutes = self::getPlayingMinutes($session);
        $timeMoney = self::calculateTimeMoney($table, $session);
        $session->load('orderItems.product');
        $serviceMoney = $session->orderItems->sum('total');
        $totalMoney = $timeMoney + $serviceMoney;

        $itemsHtml = '';
        if ($session->orderItems->isEmpty()) {
            $itemsHtml = "<p class='text-xs text-gray-500'>Chưa gọi món</p>";
        } else {
            foreach ($session->orderItems as $item) {
                $itemsHtml .= "
                <div class='flex justify-between text-xs'>
                    <span>{$item->product->name} <span class='text-gray-500'>× {$item->quantity}</span></span>
                    <span>".number_format($item->total)." đ</span>
                </div>";
            }
        }

        return new HtmlString(
            "
        <div class='space-y-2 text-sm'>
            <div class='flex justify-between'>
                <span>⏱ <strong>Thời gian:</strong> {$minutes} phút</span>
                <span class='font-semibold'>".number_format($timeMoney)." đ</span>
            </div>
            <div class='mt-2'>
                <div class='font-semibold'>🥤 Món đã gọi</div>
                <div class='mt-1 space-y-1'>{$itemsHtml}</div>
            </div>
            <div class='flex justify-between text-red-600 font-bold text-base border-t pt-2'>
                <span>TẠM TÍNH</span>
                <span>".number_format($totalMoney)." VNĐ</span>
            </div>
        </div>
        "
        );
    }

    protected static function calculateTimeMoney(TableModel $table, GameSession $session): int
    {
        $start = Carbon::parse($session->start_time);
        $end = now();
        if ($end->lessThan($start)) {
            return 0;
        }

        $rules = PricingRule::where('is_active', true)
            ->where('table_type_id', $table->table_type_id)
            ->get();

        $totalMoney = 0;
        $current = $start->copy();

        while ($current < $end) {
            $pricePerMinute = $table->price_per_hour / 60;
            $currentTimeString = $current->format('H:i:s');

            foreach ($rules as $rule) {
                $ruleStart = Carbon::parse($rule->start_time)->format('H:i:s');
                $ruleEnd = Carbon::parse($rule->end_time)->format('H:i:s');
                if ($currentTimeString >= $ruleStart && $currentTimeString < $ruleEnd) {
                    $pricePerMinute = $rule->price_per_hour / 60;
                    break;
                }
            }
            $totalMoney += $pricePerMinute;
            $current->addMinute();
        }

        return (int)ceil($totalMoney);
    }

    protected static function getPlayingMinutes(GameSession $session): int
    {
        $seconds = $session->start_time->diffInSeconds(now());
        return max(1, (int)ceil($seconds / 60));
    }

}
