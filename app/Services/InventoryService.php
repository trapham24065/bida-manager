<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryService
{

    public function orderItems(GameSession $session, array $itemsData): array
    {
        $errors = [];

        DB::transaction(function () use ($session, $itemsData, &$errors) {
            foreach ($itemsData as $item) {
                $product = Product::with('comboItems')->find($item['product_id']);
                if (!$product) {
                    continue;
                }
                $qty = $item['quantity'];

                // 1. Kiểm tra kho
                if ($product->is_combo) {
                    foreach ($product->comboItems as $child) {
                        $needed = $child->pivot->quantity * $qty;
                        if ($child->stock < $needed) {
                            $errors[] = "Thiếu hàng Combo: {$child->name}";
                        }
                    }
                } elseif ($product->stock < $qty) {
                    $errors[] = "Món {$product->name} hết hàng (Còn {$product->stock})";
                }

                if (!empty($errors)) {
                    continue;
                } // Lỗi thì bỏ qua món này

                // 2. Trừ kho
                if ($product->is_combo) {
                    foreach ($product->comboItems as $c) {
                        $c->decrement('stock', $c->pivot->quantity * $qty);
                    }
                } else {
                    $product->decrement('stock', $qty);
                }

                // 3. Lưu Order
                OrderItem::create([
                    'game_session_id' => $session->id,
                    'product_id'      => $product->id,
                    'quantity'        => $qty,
                    'price'           => $product->price,
                    'total'           => $product->price * $qty,
                ]);
            }
        });

        return $errors; // Trả về danh sách lỗi nếu có
    }

}
