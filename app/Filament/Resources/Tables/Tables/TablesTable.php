<?php

namespace App\Filament\Resources\Tables\Tables;

use App\Models\Product;
use App\Models\Table as TableModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;

use App\Services\TableService;
use App\Services\InventoryService;
use App\Services\BillingService;

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
                    ->action(function (TableModel $record) {
                        // 🟢 GỌI TABLE SERVICE
                        $service = new TableService();

                        $error = $service->checkAvailability($record);
                        if ($error) {
                            Notification::make()->title('⛔ Trùng lịch')->body($error)->danger()
                                ->actions([Action::make('view')->url('/admin/bookings')->button()])
                                ->persistent()->send();
                            return;
                        }

                        $service->startSession($record);
                        Notification::make()->title('Đã mở bàn!')->success()->send();
                    }),

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
                                                if (in_array($product->id, $topProductIds, true)) {
                                                    $badge
                                                        = "<span style='background: #ef4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 99px; font-weight: bold; margin-left: 5px;'>🔥 HOT</span>";
                                                }

                                                $html = "
                                                    <div class='flex items-center gap-2'>
                                                        <div style='position: relative;'>
                                                            <img alt='' src='{$imgUrl}' style='width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;'>
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
                        // 🟢 GỌI INVENTORY SERVICE
                        $service = new InventoryService();
                        $errors = $service->orderItems($record->currentSession, $data['items']);

                        if (!empty($errors)) {
                            Notification::make()->title('Lỗi kho')->body(implode("\n", $errors))->warning()->send();
                        } else {
                            Notification::make()->title('Lên món thành công')->success()->send();
                        }
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
                    ->action(function (TableModel $record, array $data, Action $action) {
                        // 🟢 GỌI BILLING SERVICE
                        $service = new BillingService();
                        $session = $record->currentSession;

                        // Bước 1: Tính tiền giờ & Dịch vụ
                        $timeMoney = $service->calculateTimeFee($record, $session);
                        $serviceMoney = $session->orderItems->sum('total');
                        $subTotal = $timeMoney + $serviceMoney;

                        try {
                            // Bước 2: Chốt đơn
                            $msg = $service->processCheckout($session, $data, $subTotal);

                            if ($msg) {
                                Notification::make()->title($msg)->success()->persistent()->send();
                            }

                            Notification::make()->title('Thanh toán xong!')->success()->send();
                            return redirect()->route('invoice.print', $session->id);
                        } catch (\Exception $e) {
                            Notification::make()->title('Lỗi')->body($e->getMessage())->danger()->send();
                            $action->halt();
                        }
                    }),
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

    protected static function previewBill(TableModel $table): HtmlString|string
    {
        $session = $table->currentSession;
        if (!$session) {
            return 'Không tìm thấy phiên chơi!';
        }

        // 🔥 THAY ĐỔI Ở ĐÂY: Gọi Service để tính tiền thay vì tự tính
        $billingService = new BillingService();
        $timeMoney = $billingService->calculateTimeFee($table, $session);

        // Tính phút chơi
        $minutes = max(1, (int)ceil($session->start_time->diffInSeconds(now()) / 60));

        // Load món ăn
        $session->load('orderItems.product');
        $serviceMoney = $session->orderItems->sum('total');
        $totalMoney = $timeMoney + $serviceMoney;

        // Render HTML (Phần này giữ nguyên vì nó là giao diện)
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

}
