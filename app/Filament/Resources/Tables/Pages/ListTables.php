<?php

namespace App\Filament\Resources\Tables\Pages;

use App\Filament\Resources\Tables\TableResource;
use App\Services\BillingService;
use App\Services\InventoryService;
use App\Models\Product;

// <--- Nhớ import Model
use App\Models\Customer;

// <--- Nhớ import Model

// Import các components của Form
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

// <--- Sửa lại đúng namespace của Tab
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTables extends ListRecords
{

    protected static string $resource = TableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tạo bàn'),

            Action::make('takeaway')
                ->label('Bán Mang Về')
                ->icon('heroicon-o-shopping-bag')
                ->button()
                ->color('primary')
                ->modalHeading('Đơn hàng mang về (Takeaway)')
                ->modalWidth('2xl')

                // 🔥🔥🔥 PHẦN BẠN BỊ THIẾU ĐÂY 🔥🔥🔥
                ->form([
                    // 1. Danh sách món (Bắt buộc phải có tên là 'items')
                    Repeater::make('items')
                        ->label('Chọn món')
                        ->schema([
                            Select::make('product_id')
                                ->label('Tên món')
                                ->options(Product::where('is_active', true)->pluck('name', 'id'))
                                ->required()
                                ->searchable(),

                            TextInput::make('quantity')
                                ->label('SL')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required(),
                        ])
                        ->columns(2)
                        ->required(), // <--- Quan trọng: Bắt buộc nhập để không lỗi undefined index

                    // 2. Phần thanh toán
                    Section::make('Thanh toán')->schema([
                        Select::make('customer_id')
                            ->label('Khách hàng')
                            ->options(Customer::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Khách vãng lai'),

                        Select::make('payment_method')
                            ->label('Hình thức')
                            ->options([
                                'cash'     => 'Tiền mặt',
                                'transfer' => 'CK / QR',
                            ])
                            ->default('cash')
                            ->required(),

                        TextInput::make('discount_amount')
                            ->label('Giảm giá (VNĐ)')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
                ])
                // 🔥🔥🔥 HẾT PHẦN THIẾU 🔥🔥🔥

                ->action(function (array $data) {
                    // 1. Tạo Session không có bàn (table_id = null)
                    $session = \App\Models\GameSession::create([
                        'table_id'    => null,
                        'start_time'  => now(),
                        'end_time'    => now(),
                        'status'      => 'completed',
                        'customer_id' => $data['customer_id'] ?? null,
                    ]);

                    // 2. Thêm món vào kho
                    $inventoryService = new InventoryService();
                    // Lúc này $data['items'] đã có dữ liệu từ form trên
                    $errors = $inventoryService->orderItems($session, $data['items']);

                    if (!empty($errors)) {
                        $session->delete();
                        Notification::make()->title('Lỗi kho')->body(implode("\n", $errors))->danger()->send();
                        return;
                    }

                    // 3. Tính tiền
                    $billingService = new BillingService();
                    $serviceMoney = $session->orderItems()->sum('total');

                    try {
                        $billingService->processCheckout($session, [
                            'discount_percent' => 0,
                            'discount_amount'  => $data['discount_amount'],
                            'payment_method'   => $data['payment_method'],
                            'customer_id'      => $data['customer_id'] ?? null,
                            'note'             => 'Khách mua mang về',
                        ], $serviceMoney);

                        Notification::make()->title('Đơn mang về thành công!')->success()->send();
                        return redirect()->route('invoice.print', $session->id);
                    } catch (\Exception $e) {
                        $session->delete();
                        Notification::make()->title('Lỗi')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        // (Phần Tabs giữ nguyên như của bạn)
        return [
            'all' => Tab::make('Tất cả')->icon('heroicon-m-squares-2x2'),

            'bida' => Tab::make('Khu vực Bida')
                ->icon('heroicon-m-play-circle')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('tableType', function ($q) {
                    $q->where('category', 'bida');
                })),

            'cafe' => Tab::make('Khu vực Cafe')
                ->icon('heroicon-o-trophy')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('tableType', function ($q) {
                    $q->where('category', 'cafe');
                })),
        ];
    }

}
