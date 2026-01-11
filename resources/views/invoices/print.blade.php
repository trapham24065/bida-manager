<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn #{{ $session->id }}</title>

    <style>
        @page {
            size: auto;
            margin: 0;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            width: 72mm; /* Khổ giấy in nhiệt K80 */
            margin: 0 auto;
            padding: 5px;
            background: #fff;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .line { border-bottom: 1px dashed #000; margin: 8px 0; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; border-bottom: 1px solid #000; padding-bottom: 2px;}
        td { padding: 4px 0; vertical-align: top; }

        .qr-box { margin-top: 10px; text-align: center; }
        .cash-box {
            border: 2px solid #000; padding: 8px; margin: 10px 10px;
            text-align: center; border-radius: 4px;
        }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>

<body onload="window.print()">

@php
    use Carbon\Carbon;
    $setting = \App\Models\ShopSetting::first();

    // --- 1. XỬ LÝ THỜI GIAN ---
    $start = Carbon::parse($session->start_time);
    $end   = $session->end_time ? Carbon::parse($session->end_time) : now();
    $seconds = $end->diffInSeconds($start);
    $minutes = max(1, (int) ceil($seconds / 60));
    $hours = intdiv($minutes, 60);
    $remainMinutes = $minutes % 60;

    // --- 2. XỬ LÝ TIỀN TỆ (LOGIC MỚI CÓ VAT) ---
    $finalTotal = $session->total_money;     // Khách thực trả
    $vatAmount  = $session->vat_amount ?? 0; // Tiền thuế
    $rounding   = $session->rounding_amount ?? 0; // Tiền làm tròn
    $serviceMoney = $session->orderItems->sum('total'); // Tiền nước

    // Tính giá trị giảm giá (Quy đổi ra tiền mặt)
    $discountAmount = 0;
    if ($session->discount_amount > 0) {
        $discountAmount = $session->discount_amount;
    } elseif ($session->discount_percent > 0) {
        // Nếu giảm theo %, ta cần tính ngược lại dựa trên công thức BillingService
        // Công thức: Final = (SubTotal + VAT) - (SubTotal * %) - Rounding
        // Để đơn giản hiển thị, ta lấy số xấp xỉ:
        // Discount ~ (Final + Rounding - VAT) / (1 - %) * %
        // Tuy nhiên, để chính xác nhất, ta dùng phép cộng lùi:
        // SubTotal = Final + Discount - VAT - Rounding.
        // Vì khó tính chính xác số SubTotal khi chưa biết Discount, ta dùng cách hiển thị an toàn:
        // Ta tính SubTotal tạm (chưa giảm giá)
        $tempBase = ($finalTotal - $rounding - $vatAmount);
        if ($session->discount_percent < 100) {
             $discountAmount = ($tempBase / (1 - $session->discount_percent/100)) * ($session->discount_percent/100);
        }
    }

    // Tổng gốc (SubTotal) = (Khách trả - Làm tròn) + Giảm giá - VAT
    // Đây là tổng tiền hàng + tiền giờ (Chưa thuế, chưa giảm)
    $subTotal = ($finalTotal - $rounding) + $discountAmount - $vatAmount;

    // Tiền giờ = Tổng gốc - Tiền nước
    $originalTimeMoney = $subTotal - $serviceMoney;

    // Fix lỗi làm tròn số học (đôi khi ra -1 đồng)
    if ($originalTimeMoney < 0) $originalTimeMoney = 0;

    $tableName = $session->bidaTable->name ?? 'Mang về';
@endphp

{{-- ================= HEADER ================= --}}
<div class="text-center">
    <h2 class="uppercase" style="margin: 5px 0; font-size: 16px;">
        {{ $setting->shop_name ?? 'BIDA & CAFE' }}
    </h2>
    <p style="margin:2px 0;">{{ $setting->address ?? '' }}</p>
    <p style="margin:2px 0;">SĐT: {{ $setting->phone ?? '' }}</p>
</div>

<div class="line"></div>

{{-- ================= INFO ================= --}}
<div>
    <table>
        <tr>
            <td>HĐ: <strong>#{{ $session->id }}</strong></td>
            <td class="text-right">Bàn: <strong style="font-size: 14px;">{{ $tableName }}</strong></td>
        </tr>
        <tr>
            <td>Vào: {{ $start->format('H:i') }}</td>
            <td class="text-right">Ra: {{ $end->format('H:i') }}</td>
        </tr>
        <tr>
            <td colspan="2">Ngày: {{ $end->format('d/m/Y') }} - Thu ngân: {{ auth()->user()->name ?? 'NV' }}</td>
        </tr>
    </table>
</div>

<div class="line"></div>

{{-- ================= LIST ITEM ================= --}}
<table style="margin-top: 5px;">
    <thead>
    <tr>
        <th style="width:40%">Tên món</th>
        <th style="width:15%; text-align: center;">SL</th>
        {{-- Cột VAT mới thêm --}}
        <th style="width:15%; text-align: center; font-size: 10px;">VAT</th>
        <th style="width:30%" class="text-right">Thành tiền</th>
    </tr>
    </thead>

    <tbody>

    {{-- 1. TIỀN GIỜ (Lấy % thuế từ Loại bàn) --}}
    @if($originalTimeMoney > 1000)
        @php
            // Lấy thuế suất của bàn hiện tại (Nếu bàn mang về thì là 0)
            $timeTaxRate = $session->bidaTable?->tableType?->tax_rate ?? 0;
        @endphp
        <tr>
            <td>
                <strong>Tiền giờ</strong>
                <div style="font-size: 10px; color: #555; margin-top: 2px;">
                    ({{ $hours > 0 ? $hours.'h' : '' }}{{ $remainMinutes }}p)
                </div>
            </td>
            <td style="text-align: center;">1</td>

            {{-- Hiển thị % Thuế giờ chơi --}}
            <td style="text-align: center; font-size: 10px;">
                {{ $timeTaxRate > 0 ? $timeTaxRate.'%' : '-' }}
            </td>

            <td class="text-right bold">{{ number_format($originalTimeMoney) }}</td>
        </tr>
    @endif

    {{-- 2. TIỀN MÓN ĂN / DỊCH VỤ --}}
    @foreach($session->orderItems as $item)
        <tr>
            <td>
                {{ $item->product->name }}
            </td>
            <td style="text-align: center;">{{ $item->quantity }}</td>

            {{-- Hiển thị % Thuế của từng món (Lấy từ cột tax_rate trong order_items) --}}
            <td style="text-align: center; font-size: 10px;">
                {{ ($item->tax_rate > 0) ? $item->tax_rate.'%' : '-' }}
            </td>

            <td class="text-right">{{ number_format($item->total) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="line"></div>

{{-- ================= TỔNG KẾT (PHẦN QUAN TRỌNG) ================= --}}
<div class="text-right">
    {{-- Tổng tiền hàng (Chưa thuế) --}}
    <p style="margin: 4px 0;">Cộng tiền hàng: {{ number_format($subTotal) }}</p>

    {{-- GIẢM GIÁ --}}
    @if($discountAmount > 0)
        <p style="margin: 4px 0; color: #333;">
            Giảm giá
            @if($session->discount_percent > 0)
                ({{ $session->discount_percent }}%)
            @endif:
            -{{ number_format($discountAmount) }}
        </p>
        <div style="border-bottom: 1px dotted #000; width: 60%; margin-left: auto;"></div>
    @endif

    {{-- THUẾ VAT (MỚI) --}}
    @if($vatAmount > 0)
        <p style="margin: 4px 0;">
            Thuế VAT: <strong>+{{ number_format($vatAmount) }}</strong>
        </p>
    @endif

    {{-- LÀM TRÒN --}}
    @if($rounding != 0)
        <p style="margin: 4px 0; font-style: italic; font-size: 11px;">
            Làm tròn: {{ $rounding > 0 ? '+' : '' }}{{ number_format($rounding) }}
        </p>
    @endif

    {{-- TỔNG THANH TOÁN --}}
    <p class="bold" style="font-size:16px; margin-top: 8px; border-top: 1px solid #000; padding-top: 5px;">
        KHÁCH TRẢ: {{ number_format($finalTotal) }} đ
    </p>
</div>

{{-- ================= QR / CASH ================= --}}
@if($finalTotal > 0)
    @if($session->payment_method === 'transfer' && $setting && $setting->bank_account)
        @php
            $qrUrl = "https://img.vietqr.io/image/{$setting->bank_id}-{$setting->bank_account}-qr_only.png"
                ."?amount={$finalTotal}"
                ."&addInfo=HD{$session->id}"
                ."&accountName={$setting->bank_account_name}";
        @endphp
        <div class="qr-box">
            <img src="{{ $qrUrl }}" style="width:120px; height:120px; border: 1px solid #ddd;">
            <p class="bold" style="margin: 5px 0 0 0; font-size: 11px;">QUÉT MÃ ĐỂ THANH TOÁN</p>
        </div>
    @else
        <div class="cash-box">
            <h3 style="margin: 0; font-size: 14px;">ĐÃ THANH TOÁN</h3>
        </div>
    @endif
@endif

{{-- ================= FOOTER ================= --}}
<div class="text-center" style="margin-top:15px; border-top: 1px dashed #000; padding-top: 10px;">
    <p style="font-size: 11px; margin: 0;">Wifi: {{ $setting->wifi_pass ?? '...' }}</p>
    <p style="font-size: 11px; margin-top: 4px; font-style: italic;">Hẹn gặp lại quý khách!</p>
</div>

<div class="no-print text-center" style="margin-top:30px; border-top: 1px solid #eee; padding-top: 20px;">
    <a href="/admin/tables" style="color: #555; text-decoration: none; margin-right: 15px;">⬅ Quay lại</a>
    <button onclick="window.print()"
            style="background: #000; color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
        🖨 IN HÓA ĐƠN
    </button>
</div>

</body>
</html>
