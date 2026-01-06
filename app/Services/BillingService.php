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
    public function processCheckout(GameSession $session, array $data, int $subTotal): ?string
    {
        // 1. Check Ca
        $shift = WorkShift::myCurrentShift();
        if (!$shift) {
            throw new \Exception('Chưa mở ca làm việc!');
        }

        // 2. Check Giảm giá
        $discount = ($data['discount_percent'] > 0)
            ? ($subTotal * $data['discount_percent'] / 100)
            : ($data['discount_amount'] ?? 0);

        if ($discount > $subTotal) {
            throw new \Exception('Giảm giá không hợp lệ');
        }

        $finalTotal = $subTotal - $discount;

        // 3. Làm tròn (Rounding)
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
            'rounding_amount'  => $diff,
            'payment_method'   => $data['payment_method'],
            'discount_percent' => $data['discount_percent'],
            'discount_amount'  => $data['discount_amount'],
            'note'             => $data['note'],
            'status'           => 'completed',
            'customer_id'      => $data['customer_id'],
            'work_shift_id'    => $shift->id,
        ]);

        // 5. Loyalty Logic (Tích hợp luôn ở đây cho gọn quy trình thanh toán)
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
