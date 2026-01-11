<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerRank;
use App\Models\GameSession;
use App\Models\PricingRule;
use App\Models\ShopSetting;
use App\Models\Table;
use App\Models\WorkShift;
use Illuminate\Support\Carbon;

class BillingService
{

    // Tính tiền giờ
    public function calculateTimeFee(Table $table, GameSession $session): int
    {
        if (!$table || !$session->table_id) {
            return 0;
        }
        $start = Carbon::parse($session->start_time);
        $end = now();
        if ($end->lessThan($start)) {
            return 0;
        }

        $rules = PricingRule::where('is_active', true)
            ->where('table_type_id', $table->table_type_id)->get();

        $totalMoney = 0;
        $current = $start->copy();

        while ($current < $end) {
            $pricePerMinute = $table->price_per_hour / 60;
            $nowStr = $current->format('H:i:s');
            foreach ($rules as $rule) {
                if ($nowStr >= $rule->start_time && $nowStr < $rule->end_time) {
                    $pricePerMinute = $rule->price_per_hour / 60;
                    break;
                }
            }
            $totalMoney += $pricePerMinute;
            $current->addMinute();
        }
        return (int)ceil($totalMoney);
    }

    // Xử lý thanh toán trọn gói
    // Xử lý thanh toán trọn gói
    public function processCheckout(GameSession $session, array $data, int $subTotal): ?string
    {
        // 1. Check Ca
        $shift = WorkShift::myCurrentShift();
        if (!$shift) {
            throw new \RuntimeException('Chưa mở ca làm việc!');
        }

        // ====================================================
        // === A. TÍNH THUẾ VAT (LOGIC MỚI THÊM VÀO ĐÂY) ===
        // ====================================================
        $session->load('orderItems');
        // A1. Tính thuế của các Món ăn (Dựa trên tax_rate của từng món)
        // Lưu ý: Cần đảm bảo bạn đã chạy migration thêm cột tax_rate vào order_items
        $productTaxAmount = $session->orderItems->sum(function ($item) {
            $taxRate = $item->tax_rate ?? 0; // Lấy thuế lưu trong món, nếu không có thì bằng 0
            return ($item->total * $taxRate) / 100;
        });

        // A2. Tính thuế của Giờ chơi (Dựa trên tax_rate của Loại bàn)
        $timeTaxAmount = 0;

        // Chỉ tính nếu có bàn (Không phải mang về)
        if ($session->table_id && $session->table) {
            // Tính lại tiền giờ để biết bao nhiêu mà đánh thuế
            $timeMoney = $this->calculateTimeFee($session->table, $session);

            // Lấy % thuế của loại bàn (VD: 10%)
            $timeTaxRate = $session->table->tableType->tax_rate ?? 0;

            $timeTaxAmount = ($timeMoney * $timeTaxRate) / 100;
        }

        // A3. Tổng tiền thuế VAT
        $totalVatAmount = $productTaxAmount + $timeTaxAmount;

        // ====================================================

        // 2. Check Giảm giá
        $discount = 0;
        if ($data['discount_percent'] > 0) {
            $discount = ($subTotal * $data['discount_percent']) / 100;
        } else {
            $discount = $data['discount_amount'] ?? 0;
        }

        // Công thức: (Tiền hàng + Tiền giờ) + VAT - Giảm giá
        // Hoặc: (SubTotal - Giảm giá) + VAT -> Tùy chính sách quán, nhưng thường VAT tính trên giá gốc
        // Ở đây mình làm: Tổng Gốc + VAT - Giảm giá = Khách trả
        $totalWithTax = $subTotal + $totalVatAmount;
        $finalTotal = $totalWithTax - $discount;

        if ($finalTotal < 0) {
            $finalTotal = 0;
        } // Không để âm tiền

        // 3. Làm tròn (Rounding) - Áp dụng trên số cuối cùng
        $setting = ShopSetting::first();
        $mode = $setting?->rounding_mode ?? 'none';
        $original = $finalTotal;

        if ($finalTotal > 0 && $mode !== 'none') {
            if ($mode === 'down') {
                $finalTotal = floor($original / 1000) * 1000;
            } elseif ($mode === 'up') {
                $finalTotal = ceil($original / 1000) * 1000;
            } elseif ($mode === 'auto') {
                $finalTotal = round($original / 1000) * 1000;
            }
        }
        $diff = $finalTotal - $original;

        // 4. Update Database
        $session->update([
            'end_time'         => now(),
            'total_money'      => $finalTotal,
            'vat_amount'       => $totalVatAmount, // <--- LƯU TIỀN THUẾ VÀO ĐÂY
            'rounding_amount'  => $diff,
            'payment_method'   => $data['payment_method'],
            'discount_percent' => $data['discount_percent'],
            'discount_amount'  => $data['discount_amount'],
            'note'             => $data['note'],
            'status'           => 'completed',
            'customer_id'      => $data['customer_id'],
            'work_shift_id'    => $shift->id,
        ]);

        // 5. Loyalty Logic
        return $this->processLoyalty($data['customer_id'], $finalTotal);
    }

    private function processLoyalty($customerId, $amount): ?string
    {
        if (!$customerId) {
            return null;
        }
        $customer = Customer::find($customerId);
        if (!$customer) {
            return null;
        }

        $customer->total_spending += $amount;
        $customer->points += floor($amount / 100000);

        // Check Rank
        $newRank = CustomerRank::where('min_spending', '<=', $customer->total_spending)
            ->orderByDesc('min_spending')->first();

        $msg = null;
        if ($newRank && $customer->customer_rank_id !== $newRank->id) {
            $customer->customer_rank_id = $newRank->id;
            $msg = "Khách {$customer->name} lên hạng {$newRank->name}!";
        }
        $customer->save();
        return $msg;
    }

}
