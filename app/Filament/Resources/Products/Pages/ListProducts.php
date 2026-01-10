<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\Blade;
use Filament\Support\Facades\FilamentView;

class ListProducts extends ListRecords
{

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tạo sản phẩm'),
        ];
    }

// === CHIA NHÓM ===
    public function getTabs(): array
    {
        $categories = \App\Models\Category::where('is_active', true)
            ->withCount('products')
            ->get();

        $tabs = [
            'all' => Tab::make('Tất cả')
                ->icon('heroicon-m-list-bullet')
                ->badge(\App\Models\Product::count()),
        ];

        foreach ($categories as $category) {
            $tabs[$category->id] = Tab::make($category->name)
                ->badge($category->products_count)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('category_id', $category->id));
        }
        return $tabs;
    }

    public function mount(): void
    {
        parent::mount();

        // Đăng ký CSS
        FilamentView::registerRenderHook(
            'panels::head.end', // [SỬA LẠI] Đổi hook này để đảm bảo CSS load vào thẻ <head>
            fn(): string => Blade::render(
                <<<'HTML'
                <style>
                    /* 1. Tác động vào khung chứa Tabs (Sửa selector thành .fi-tabs-list) */
                    .fi-tabs-list {
                        background-color: white;
                        border: 1px solid #e5e7eb; /* Viền xám */
                        border-radius: 0.5rem;
                        padding: 0 !important;
                        gap: 0 !important; /* Dính liền nhau không có khe hở */
                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                        display: inline-flex;
                        overflow: visible !important;
                    }

                    /* 2. Tác động vào từng nút Tab */
                    .fi-tabs-item {
                        border-right: 1px solid #e5e7eb;
                        border-radius: 0 !important;
                        padding-left: 1rem !important;
                        padding-right: 1rem !important;
                        position: relative;
                        overflow: visible !important; /* Cho phép số badge lòi ra ngoài */
                    }

                    /* Xóa viền phải của tab cuối cùng */
                    .fi-tabs-item:last-child {
                        border-right: none;
                    }

                    /* Bo tròn nút đầu và nút cuối */
                    .fi-tabs-item:first-child {
                        border-top-left-radius: 0.5rem !important;
                        border-bottom-left-radius: 0.5rem !important;
                    }
                    .fi-tabs-item:last-child {
                        border-top-right-radius: 0.5rem !important;
                        border-bottom-right-radius: 0.5rem !important;
                    }

                    /* 3. Chỉnh cái số (Badge) bay lên góc */
                    .fi-tabs-item .fi-badge {
                        position: absolute;
                        top: -8px !important;    /* Bay lên trên */
                        right: -8px !important;  /* Bay sang phải */
                        transform: scale(0.8);   /* Nhỏ lại xíu */
                        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
                        z-index: 20;
                        border: 1px solid white; /* Viền trắng cho nổi */
                    }

                    /* Fix lỗi khi active badge bị chìm */
                    .fi-tabs-item span {
                         overflow: visible !important;
                    }
                </style>
            HTML
            )
        );
    }

}
