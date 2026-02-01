<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                    'cost'            => $product->cost_price,
                    'total'           => $product->price * $qty,
                    'tax_rate'        => $product->tax_rate,
                ]);
            }
        });

        return $errors; // Trả về danh sách lỗi nếu có
    }

    /**
     * Hàm trả lại món (Hủy món)
     */
    public function returnItem(GameSession $session, int $productId, int $quantityToReturn)
    {
        return DB::transaction(function () use ($session, $productId, $quantityToReturn) {
            // 1. Tìm dòng OrderItem tương ứng
            $orderItem = OrderItem::where('game_session_id', $session->id)
                ->where('product_id', $productId)
                ->first();

            if (!$orderItem) {
                throw ValidationException::withMessages(['product' => 'Món này chưa được gọi trong bàn này.']);
            }

            if ($quantityToReturn > $orderItem->quantity) {
                throw ValidationException::withMessages(
                    ['quantity' => "Khách chỉ gọi {$orderItem->quantity}, không thể trả {$quantityToReturn}."]
                );
            }

            $product = Product::with('comboItems')->find($productId);

            // 2. Cộng lại tồn kho (Restock)
            if ($product->is_combo) {
                foreach ($product->comboItems as $child) {
                    // Cộng lại số lượng hàng con tương ứng
                    $child->increment('stock', $child->pivot->quantity * $quantityToReturn);
                }
            } else {
                // Cộng lại hàng đơn
                $product->increment('stock', $quantityToReturn);
            }

            // 3. Cập nhật hoặc Xóa OrderItem
            $remainingQty = $orderItem->quantity - $quantityToReturn;

            if ($remainingQty > 0) {
                // Nếu vẫn còn -> Cập nhật số lượng và tổng tiền
                $orderItem->update([
                    'quantity' => $remainingQty,
                    'total'    => $remainingQty * $orderItem->price,
                    // Thuế giữ nguyên % nên không cần sửa tax_rate
                ]);
            } else {
                // Nếu trả hết -> Xóa luôn dòng này khỏi database
                $orderItem->delete();
            }
        });
    }

}
