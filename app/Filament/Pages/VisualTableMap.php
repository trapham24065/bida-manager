<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\GameSession;
use App\Models\Product;
use App\Models\Table;
use App\Services\BillingService;
use App\Services\InventoryService;
use App\Services\TableService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class VisualTableMap extends Page implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Sơ đồ bàn (Map)';

    protected static ?string $title = 'Sơ đồ bố trí bàn';

    protected string $view = 'filament.pages.visual-table-map';

    // Biến để lưu trạng thái bật/tắt chế độ sửa
    public $isEditMode = false;

    // Bàn đang được chọn
    public ?int $selectedTableId = null;

    // Load danh sách bàn kèm thông tin trạng thái
    public function getViewData(): array
    {
        return [
            'tables' => Table::with([
                'currentSession',
                'tableType', // Load loại bàn để phân biệt bida/cafe
                'bookings' => function ($q) {
                    // Lấy booking sắp tới trong 1 tiếng
                    $q->where('status', 'pending')
                        ->where('booking_time', '>=', now())
                        ->where('booking_time', '<=', now()->addHour());
                },
            ])->get(),
        ];
    }

    // Hàm lưu vị trí mới (Được gọi từ Javascript)
    public function updateTablePosition($id, $x, $y): void
    {
        Table::whereKey($id)->update([
            'position_x' => (int) $x,
            'position_y' => (int) $y,
        ]);
    }

    // Nút bật/tắt chế độ chỉnh sửa trên Header
    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleEdit')
                ->label(fn() => $this->isEditMode ? 'Lưu & Khóa sơ đồ' : 'Chỉnh sửa vị trí')
                ->icon(fn() => $this->isEditMode ? 'heroicon-o-check' : 'heroicon-o-pencil')
                ->action(fn() => $this->isEditMode = !$this->isEditMode),
        ];
    }

    // ========================================
    // ACTION: BẮT ĐẦU PHIÊN CHƠI
    // ========================================
    public function startAction(): Action
    {
        return Action::make('start')
            ->label('Bắt đầu')
            ->icon('heroicon-o-play')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(fn(array $arguments) => 'Mở bàn ' . Table::find($arguments['table'])?->name)
            ->modalDescription('Xác nhận bắt đầu phiên chơi mới?')
            ->action(function (array $arguments) {
                $table = Table::find($arguments['table']);
                if (!$table) return;

                $service = new TableService();
                $error = $service->checkAvailability($table);

                if ($error) {
                    Notification::make()
                        ->title('⛔ Trùng lịch')
                        ->body($error)
                        ->danger()
                        ->persistent()
                        ->send();
                    return;
                }

                $service->startSession($table);
                Notification::make()->title('Đã mở bàn!')->success()->send();
            });
    }

    // ========================================
    // ACTION: GỌI MÓN
    // ========================================
    public function orderAction(): Action
    {
        return Action::make('order')
            ->label('Gọi món')
            ->icon('heroicon-o-shopping-cart')
            ->color('warning')
            ->modalHeading(fn(array $arguments) => 'Gọi món - ' . Table::find($arguments['table'])?->name)
            ->modalWidth('lg')
            ->form([
                Repeater::make('items')
                    ->label('Danh sách món')
                    ->schema([
                        Select::make('product_id')
                            ->label('Món')
                            ->options(function () {
                                return Product::where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(function ($product) {
                                        $imgUrl = $product->image
                                            ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->image)
                                            : 'https://placehold.co/50x50?text=No+Img';

                                        $html = "
                                            <div class='flex items-center gap-2'>
                                                <img alt='' src='{$imgUrl}' style='width: 40px; height: 40px; object-fit: cover; border-radius: 6px;'>
                                                <div>
                                                    <div class='font-bold text-sm'>{$product->name}</div>
                                                    <div class='text-xs text-gray-500'>" . number_format($product->price) . " đ</div>
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
            ->action(function (array $arguments, array $data) {
                $table = Table::find($arguments['table']);
                if (!$table || !$table->currentSession) return;

                $service = new InventoryService();
                $errors = $service->orderItems($table->currentSession, $data['items']);

                if (!empty($errors)) {
                    Notification::make()->title('Lỗi kho')->body(implode("\n", $errors))->warning()->send();
                } else {
                    Notification::make()->title('Lên món thành công!')->success()->send();
                }
            });
    }

    // ========================================
    // ACTION: TÍNH TIỀN
    // ========================================
    public function stopAction(): Action
    {
        return Action::make('stop')
            ->label('Tính tiền')
            ->icon('heroicon-o-banknotes')
            ->color('danger')
            ->modalHeading(fn(array $arguments) => 'Thanh toán - ' . Table::find($arguments['table'])?->name)
            ->modalDescription('Kiểm tra kỹ hóa đơn trước khi thanh toán')
            ->modalSubmitActionLabel('✅ Thanh toán')
            ->modalWidth('lg')
            ->form(function (array $arguments) {
                $table = Table::find($arguments['table']);
                return [
                    Placeholder::make('bill_preview')
                        ->label('Tạm tính')
                        ->content(fn() => $this->previewBill($table)),

                    Select::make('customer_id')
                        ->label('Khách hàng thành viên')
                        ->options(Customer::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->placeholder('Khách vãng lai'),

                    Select::make('payment_method')
                        ->label('Hình thức thanh toán')
                        ->options([
                            'cash' => 'Tiền mặt',
                            'transfer' => 'Chuyển khoản / QR',
                        ])
                        ->default('cash')
                        ->required()
                        ->native(false),

                    Section::make('Ưu đãi / Giảm giá')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('discount_percent')
                                    ->label('Giảm theo %')
                                    ->numeric()
                                    ->minValue(0)->maxValue(100)
                                    ->default(0)
                                    ->suffix('%'),
                                TextInput::make('discount_amount')
                                    ->label('Giảm tiền mặt')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('VNĐ'),
                            ]),
                            Textarea::make('note')
                                ->label('Ghi chú')
                                ->placeholder('VD: Khách quen...'),
                        ]),
                ];
            })
            ->action(function (array $arguments, array $data) {
                $table = Table::find($arguments['table']);
                if (!$table || !$table->currentSession) return;

                $service = new BillingService();
                $session = $table->currentSession;

                $timeMoney = $service->calculateTimeFee($table, $session);
                $serviceMoney = $session->orderItems->sum('total');
                $subTotal = $timeMoney + $serviceMoney;

                try {
                    $msg = $service->processCheckout($session, $data, $subTotal);

                    if ($msg) {
                        Notification::make()->title($msg)->success()->persistent()->send();
                    }

                    Notification::make()->title('Thanh toán thành công!')->success()->send();
                    return redirect()->route('invoice.print', $session->id);
                } catch (\Exception $e) {
                    Notification::make()->title('Lỗi')->body($e->getMessage())->danger()->send();
                }
            });
    }

    // ========================================
    // XEM CHI TIẾT PHIÊN CHƠI
    // ========================================
    public function viewSessionAction(): Action
    {
        return Action::make('viewSession')
            ->label('Xem chi tiết')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->modalHeading(fn(array $arguments) => 'Chi tiết - ' . Table::find($arguments['table'])?->name)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Đóng')
            ->modalContent(function (array $arguments) {
                $table = Table::find($arguments['table']);
                if (!$table || !$table->currentSession) {
                    return new HtmlString('<p class="text-gray-500">Không có phiên chơi nào.</p>');
                }
                return $this->previewBill($table);
            });
    }

    // ========================================
    // HELPER: XEM TRƯỚC HÓA ĐƠN
    // ========================================
    protected function previewBill(Table $table): HtmlString|string
    {
        $session = $table->currentSession;
        if (!$session) {
            return 'Không tìm thấy phiên chơi!';
        }

        $billingService = new BillingService();
        $timeMoney = $billingService->calculateTimeFee($table, $session);
        $minutes = max(1, (int) ceil($session->start_time->diffInSeconds(now()) / 60));

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
                    <span>" . number_format($item->total) . " đ</span>
                </div>";
            }
        }

        return new HtmlString("
            <div class='space-y-2 text-sm'>
                <div class='flex justify-between'>
                    <span>⏱ <strong>Thời gian:</strong> {$minutes} phút</span>
                    <span class='font-semibold'>" . number_format($timeMoney) . " đ</span>
                </div>
                <div class='mt-2'>
                    <div class='font-semibold'>🥤 Món đã gọi</div>
                    <div class='mt-1 space-y-1'>{$itemsHtml}</div>
                </div>
                <div class='flex justify-between text-red-600 font-bold text-base border-t pt-2'>
                    <span>TẠM TÍNH</span>
                    <span>" . number_format($totalMoney) . " VNĐ</span>
                </div>
            </div>
        ");
    }
}
