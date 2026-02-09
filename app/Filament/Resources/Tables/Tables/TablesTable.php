<?php

namespace App\Filament\Resources\Tables\Tables;

use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Table as TableModel;
use Filament\Actions\Action;

use App\Services\TableService;
use App\Services\TableManagementService;
use App\Services\InventoryService;
use App\Services\BillingService;

// Import Action chuẩn
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

// Import Grid của Form
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\View;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use Filament\Schemas\Components\Utilities\Set;

class TablesTable
{

    public static function configure(Table $table): Table
    {
        return $table
            // 1. CẤU HÌNH LƯỚI
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
                '2xl' => 4,
            ])

            // 2. GIAO DIỆN CARD
            ->columns([
                View::make('filament.tables.columns.bida-card'),
            ])

            // 3. CẤU HÌNH ACTION
            // 3. CẤU HÌNH ACTION
            ->recordActions([
                // ==================================================
                // 1. NÚT BẮT ĐẦU
                // ==================================================
                Action::make('start')
                    ->label('Mở bàn')
                    ->button()
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn(TableModel $record) => !$record->hasRunningSession())
                    ->requiresConfirmation()
                    ->action(function (TableModel $record) {
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
                // ==================================================
                // 4. NÚT THANH TOÁN
                // ==================================================
                Action::make('stop')
                    ->label('Thanh toán')
                    ->button()
                    ->icon('heroicon-o-banknotes')
                    ->color('danger')
                    ->visible(fn(TableModel $record) => $record->hasRunningSession())
                    ->modalHeading('Thanh toán')
                    ->modalSubmitActionLabel('✅ Thanh toán & In')
                    ->modalWidth('lg')
                    ->form([
                        Placeholder::make('bill_preview')->label('Tạm tính')
                            ->content(fn(TableModel $record) => self::previewBill($record)),

                        Select::make('customer_id')->label('Khách hàng')
                            ->options(\App\Models\Customer::all()->pluck('name', 'id'))
                            ->searchable()->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('phone')->required(),
                            ])
                            ->live()->afterStateUpdated(function ($state, Set $set) {
                                $discount = \App\Models\Customer::with('rank')->find($state)?->rank->discount_percent ??
                                    0;
                                $set('discount_percent', $discount);
                                if ($discount > 0) {
                                    Notification::make()->title("Đã áp dụng giảm {$discount}%")->success()->send();
                                }
                            }),

                        Select::make('payment_method')->options(['cash' => 'Tiền mặt', 'transfer' => 'Chuyển khoản'])
                            ->default('cash')->required(),

                        Section::make('Giảm giá')->schema([
                            Grid::make(2)->schema([
                                TextInput::make('discount_percent')->numeric()->suffix('%')->live()->afterStateUpdated(
                                    fn(Set $set) => $set('discount_amount', 0)
                                ),
                                TextInput::make('discount_amount')->numeric()->suffix('VNĐ')->live()->afterStateUpdated(
                                    fn(Set $set) => $set('discount_percent', 0)
                                ),
                            ]),
                            Textarea::make('note')->label('Ghi chú'),
                        ]),
                    ])
                    ->action(function (TableModel $record, array $data, Action $action) {
                        $service = new BillingService();
                        $session = $record->currentSession;
                        $session->load('orderItems');

                        $subTotal = $service->calculateTimeFee($record, $session) + $session->orderItems->sum('total');
                        $msg = $service->processCheckout($session, $data, $subTotal);

                        if ($msg) {
                            Notification::make()->title($msg)->success()->persistent()->send();
                        }
                        Notification::make()->title('Thanh toán xong!')->success()->send();
                        return redirect()->route('invoice.print', $session->id);
                    }),

                // ==================================================
                // 3. MENU TIỆN ÍCH (Action Group)
                // ==================================================
                ActionGroup::make([
                    // ==================================================
                    // 2. NÚT GỌI MÓN (Quan trọng)
                    // ==================================================
                    Action::make('order')
                        ->label('Gọi món')
                        ->button()
                        ->icon('heroicon-o-shopping-cart')
                        ->color('warning')
                        ->visible(fn(TableModel $record) => $record->hasRunningSession())
                        ->modalHeading('📋 Gọi Món')
                        ->modalWidth('4xl')
                        ->form([
                            Repeater::make('items')
                                ->label('Danh sách món')
                                ->schema([
                                    Select::make('product_id')
                                        ->label('Chọn Sản Phẩm')
                                        ->options(function () {
                                            $topProductIds = \App\Models\OrderItem::select(
                                                'product_id',
                                                DB::raw('SUM(quantity) as total')
                                            )
                                                ->groupBy('product_id')->orderByDesc('total')->limit(5)->pluck(
                                                    'product_id'
                                                )
                                                ->toArray();
                                            return Product::where('is_active', true)->get()->mapWithKeys(
                                                function ($product) use ($topProductIds) {
                                                    $imgUrl = $product->image
                                                        ? \Illuminate\Support\Facades\Storage::disk('public')->url(
                                                            $product->image
                                                        )
                                                        : 'https://placehold.co/80x80?text=No+Image';

                                                    $badge = in_array($product->id, $topProductIds, true)
                                                        ? "<span style='background: #ef4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 99px; font-weight: bold;'>🔥 HOT</span>"
                                                        : "";

                                                    $html = "
                                                        <div style='display: flex; align-items: center; gap: 12px; padding: 8px;'>
                                                            <img src='{$imgUrl}' style='width: 70px; height: 70px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;'>
                                                            <div>
                                                                <div style='font-size: 14px; font-weight: 600;'>{$product->name} {$badge}</div>
                                                                <div style='font-size: 12px; color: #ef4444; font-weight: bold;'>"
                                                        .number_format($product->price)." đ</div>
                                                            </div>
                                                        </div>";
                                                    return [$product->id => $html];
                                                }
                                            );
                                        })
                                        ->required()
                                        ->searchable()
                                        ->allowHtml(),
                                    TextInput::make('quantity')
                                        ->label('Số Lượng')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->columnSpan('auto'),
                                ])
                                ->columns(1)
                                ->addActionLabel('➕ Thêm Món'),
                        ])
                        ->action(function (TableModel $record, array $data) {
                            $service = new InventoryService();
                            $errors = $service->orderItems($record->currentSession, $data['items']);
                            if (!empty($errors)) {
                                Notification::make()->title('⚠️ Lỗi kho')->body(implode("\n", $errors))->warning()
                                    ->send();
                            } else {
                                Notification::make()->title('✅ Lên món thành công')->success()->send();
                            }
                        }),
                    // --- A. TẠM DỪNG ---
                    Action::make('pause')
                        ->label(
                            fn(TableModel $record) => $record->currentSession?->isPaused() ? 'Tiếp tục tính giờ'
                                : 'Tạm dừng tính giờ'
                        )
                        ->icon(
                            fn(TableModel $record) => $record->currentSession?->isPaused() ? 'heroicon-o-play'
                                : 'heroicon-o-pause'
                        )
                        ->color(fn(TableModel $record) => $record->currentSession?->isPaused() ? 'success' : 'gray')
                        ->requiresConfirmation()
                        ->action(function (TableModel $record) {
                            $session = $record->currentSession;
                            if ($session->isPaused()) {
                                $session->resume();
                                Notification::make()->title('Đã tiếp tục!')->success()->send();
                            } else {
                                $session->pause();
                                Notification::make()->title('Đã tạm dừng!')->info()->send();
                            }
                        }),

                    // --- B. TRẢ MÓN ---
                    Action::make('return_item')
                        ->label('Trả/Hủy món')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('danger')
                        ->form(function (TableModel $record) {
                            $session = $record->currentSession;
                            if (!$session) {
                                return [];
                            }
                            return [
                                Select::make('product_id')->label('Món trả')
                                    ->options(
                                        OrderItem::where('game_session_id', $session->id)
                                            ->join('products', 'order_items.product_id', '=', 'products.id')
                                            ->get()->mapWithKeys(
                                                fn($i) => [$i->product_id => "{$i->name} (SL: {$i->quantity})"]
                                            )
                                    )
                                    ->required()->live()->afterStateUpdated(fn(Set $set) => $set('quantity', 1)),
                                TextInput::make('quantity')->label('SL')->numeric()->default(1)->minValue(1)->required()
                                    // Sửa lỗi Get ở đây
                                    ->maxValue(function (Get $get) use ($session) {
                                        $productId = $get('product_id');
                                        return OrderItem::where('game_session_id', $session->id)->where(
                                            'product_id',
                                            $productId
                                        )->first()?->quantity ?? 1;
                                    }),
                            ];
                        })
                        ->action(function (TableModel $record, array $data) {
                            (new InventoryService())->returnItem(
                                $record->currentSession,
                                $data['product_id'],
                                $data['quantity']
                            );
                            Notification::make()->title('Đã trả món')->success()->send();
                        }),

                    // --- C. ĐỔI BÀN (FIX LỖI $record) ---
                    Action::make('transfer')
                        ->label('Chuyển bàn')
                        ->icon('heroicon-o-arrows-right-left')
                        ->form([
                            Select::make('new_table_id')
                                ->label('Sang bàn')
                                // 👇 QUAN TRỌNG: Truyền TableModel $record vào đây
                                ->options(fn(TableModel $record) => TableModel::where('is_active', true)
                                    ->where('id', '!=', $record->id)
                                    ->get()
                                    ->mapWithKeys(fn($t) => [$t->id => $t->name])
                                )
                                ->required(),
                            Textarea::make('reason')->label('Lý do'),
                        ])
                        ->action(function (TableModel $record, array $data) {
                            (new TableManagementService())->transferTableSession(
                                $record->currentSession,
                                $data['new_table_id'],
                                $data['reason'] ?? ''
                            );
                            Notification::make()->title('Đổi bàn thành công')->success()->send();
                        }),

                    // --- D. GHÉP ĐƠN (FIX LỖI $record) ---
                    Action::make('merge')
                        ->label('Ghép đơn')
                        ->icon('heroicon-o-link')
                        ->form([
                            Select::make('target_session_id')
                                ->label('Ghép vào')
                                // 👇 QUAN TRỌNG: Truyền TableModel $record vào đây
                                ->options(fn(TableModel $record) => \App\Models\GameSession::where('status', 'running')
                                    ->where('id', '!=', $record->currentSession?->id)
                                    ->get()
                                    ->mapWithKeys(fn($s) => [$s->id => $s->bidaTable?->name])
                                )
                                ->required(),
                        ])
                        ->action(function (TableModel $record, array $data) {
                            (new TableManagementService())->mergeSession(
                                $record->currentSession,
                                \App\Models\GameSession::find($data['target_session_id'])
                            );
                            Notification::make()->title('Ghép thành công')->success()->send();
                        }),
                    // ==================================================
                    // ACTION: XEM HÓA ĐƠN
                    // ==================================================
                    Action::make('view_bill')
                        ->label('📄 Xem hóa đơn')
                        ->icon('heroicon-o-receipt-refund')
                        ->color('info')
                        ->visible(fn(TableModel $record) => $record->hasRunningSession())
                        ->modalHeading('Hóa Đơn Hiện Tại')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Đóng')
                        ->modalWidth('lg')
                        ->modalContent(function (TableModel $record) {
                            $table = $record;
                            $session = $table->currentSession;
                            if (!$session) {
                                return 'Không tìm thấy phiên chơi!';
                            }

                            $billingService = new BillingService();
                            $session->load('orderItems.product');

                            $timeMoney = $billingService->calculateTimeFee($table, $session);
                            $actualMinutes = $session->getActualPlayingMinutes();
                            $pausedMinutes = (int)floor($session->getTotalPausedSeconds() / 60);

                            $timeTaxRate = $table->tableType->tax_rate ?? 0;
                            $timeTax = ($timeMoney * $timeTaxRate) / 100;

                            $serviceMoney = 0;
                            $productTax = 0;

                            foreach ($session->orderItems as $item) {
                                $serviceMoney += $item->total;
                                $rate = $item->tax_rate ?? 0;
                                $productTax += ($item->total * $rate) / 100;
                            }

                            $totalVat = $timeTax + $productTax;
                            $finalTotal = $timeMoney + $serviceMoney + $totalVat;

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

                            $vatHtml = '';
                            if ($totalVat > 0) {
                                $vatHtml = "
                            <div class='flex justify-between text-xs text-gray-600 mt-1'>
                                <span>VAT (Ước tính)</span>
                                <span>+".number_format($totalVat)." đ</span>
                            </div>";
                            }

                            $pauseInfo = '';
                            if ($pausedMinutes > 0) {
                                $pauseInfo
                                    = "<span class='text-gray-500 text-xs'>(Đã dừng: {$pausedMinutes} phút)</span>";
                            }
                            if ($session->isPaused()) {
                                $pauseInfo .= "<span class='text-orange-500 text-xs font-bold ml-1'>⏸ ĐANG TẠM DỪNG</span>";
                            }

                            return new HtmlString(
                                "
                            <div class='space-y-3 text-sm'>
                                <div class='bg-blue-50 border border-blue-200 rounded-lg p-3'>
                                    <div class='text-xs text-blue-600 font-semibold mb-2'>⏱ THỜI GIAN CHƠI</div>
                                    <div class='flex justify-between items-center'>
                                        <span class='text-lg font-bold text-blue-900'>{$actualMinutes} phút</span>
                                        <span class='text-lg font-bold text-red-600'>".number_format($timeMoney)." đ</span>
                                    </div>
                                    <div class='mt-1 text-xs text-gray-600'>{$pauseInfo}</div>
                                </div>

                                <div class='bg-green-50 border border-green-200 rounded-lg p-3'>
                                    <div class='text-xs text-green-600 font-semibold mb-2'>🥤 MÓN ĐÃ GỌI</div>
                                    <div class='space-y-1'>{$itemsHtml}</div>
                                    <div class='flex justify-between text-xs font-semibold mt-2 pt-2 border-t border-green-200'>
                                        <span>Tổng tiền hàng:</span>
                                        <span>".number_format($serviceMoney)." đ</span>
                                    </div>
                                </div>

                                {$vatHtml}

                                <div class='bg-red-50 border-2 border-red-300 rounded-lg p-3'>
                                    <div class='flex justify-between items-center'>
                                        <span class='text-base font-bold text-red-900'>TẠMP TÍNH:</span>
                                        <span class='text-2xl font-bold text-red-600'>".number_format($finalTotal)." đ</span>
                                    </div>
                                    <p class='text-[10px] text-gray-500 mt-1'>(Chưa bao gồm ưu đãi thành viên)</p>
                                </div>
                            </div>
                        "
                            );
                        }),
                ])
                    ->label('Tiện ích')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button()
                    ->visible(fn(TableModel $record) => $record->hasRunningSession()),

            ])
            ->toolbarActions([]);
    }

    /* =========================================================
     | BUSINESS LOGIC (PREVIEW BILL)
     | Đã cập nhật để hiển thị VAT
     ========================================================= */

    protected static function previewBill(TableModel $table): HtmlString|string
    {
        $session = $table->currentSession;
        if (!$session) {
            return 'Không tìm thấy phiên chơi!';
        }

        $billingService = new BillingService();
        $session->load('orderItems.product'); // Load món

        // 1. Tính Tiền Giờ & Thuế Giờ (đã trừ thời gian tạm dừng)
        $timeMoney = $billingService->calculateTimeFee($table, $session);
        $actualMinutes = $session->getActualPlayingMinutes();
        $pausedMinutes = (int)floor($session->getTotalPausedSeconds() / 60);

        $timeTaxRate = $table->tableType->tax_rate ?? 0;
        $timeTax = ($timeMoney * $timeTaxRate) / 100;

        // 2. Tính Tiền Nước & Thuế Nước
        $serviceMoney = 0;
        $productTax = 0;

        foreach ($session->orderItems as $item) {
            $serviceMoney += $item->total;
            // Tính thuế từng món
            $rate = $item->tax_rate ?? 0;
            $productTax += ($item->total * $rate) / 100;
        }

        // 3. Tổng hợp
        $totalVat = $timeTax + $productTax;
        $finalTotal = $timeMoney + $serviceMoney + $totalVat;

        // 4. Render HTML
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

        // Hiển thị dòng VAT nếu có
        $vatHtml = '';
        if ($totalVat > 0) {
            $vatHtml = "
            <div class='flex justify-between text-xs text-gray-600 mt-1'>
                <span>VAT (Ước tính)</span>
                <span>+".number_format($totalVat)." đ</span>
            </div>";
        }

        // Hiển thị thông tin tạm dừng nếu có
        $pauseInfo = '';
        if ($pausedMinutes > 0) {
            $pauseInfo = "<span class='text-gray-500 text-xs'>(Đã dừng: {$pausedMinutes} phút)</span>";
        }
        if ($session->isPaused()) {
            $pauseInfo .= "<span class='text-orange-500 text-xs font-bold ml-1'>⏸ ĐANG TẠM DỪNG</span>";
        }

        return new HtmlString(
            "
            <div class='space-y-2 text-sm'>
                <div class='flex justify-between'>
                    <span>⏱ <strong>Giờ chơi:</strong> {$actualMinutes} phút {$pauseInfo}</span>
                    <span class='font-semibold'>".number_format($timeMoney)." đ</span>
                </div>

                <div class='mt-2'>
                    <div class='font-semibold'>🥤 Món đã gọi</div>
                    <div class='mt-1 space-y-1'>{$itemsHtml}</div>
                </div>

                <div class='border-t pt-2 mt-2'>
                    {$vatHtml}
                    <div class='flex justify-between text-red-600 font-bold text-base mt-1'>
                        <span>KHÁCH TRẢ</span>
                        <span>".number_format($finalTotal)." VNĐ</span>
                    </div>
                    <p class='text-[10px] text-gray-400 text-right italic'>(Chưa bao gồm giảm giá thành viên)</p>
                </div>
            </div>
        "
        );
    }

}
