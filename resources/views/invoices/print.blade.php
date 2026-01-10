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
            width: 72mm; /* Khổ K80 */
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

    // 1. TÍNH THỜI GIAN
    $start = Carbon::parse($session->start_time);
    $end   = $session->end_time ? Carbon::parse($session->end_time) : now();
    $seconds = $end->diffInSeconds($start);

    // Tính giờ hiển thị
    $minutes = max(1, (int) ceil($seconds / 60));
    $hours = intdiv($minutes, 60);
    $remainMinutes = $minutes % 60;

    // 2. TÍNH TOÁN TIỀN (Logic hiển thị ngược)
    $finalTotal = $session->total_money;
    $serviceMoney = $session->orderItems->sum('total');

    // Logic tính ngược SubTotal
    $tempSubTotal = $finalTotal;
    if ($session->discount_percent > 0 && $session->discount_percent < 100) {
         $tempSubTotal = $finalTotal / (1 - ($session->discount_percent / 100));
    } else {
        $tempSubTotal = $finalTotal + ($session->discount_amount ?? 0);
    }

    // Tiền giờ = Tổng (chưa giảm) - Tiền nước
    $originalTimeMoney = $tempSubTotal - $serviceMoney;

    // Fix lỗi làm tròn số âm nhỏ
    if ($originalTimeMoney < 0) $originalTimeMoney = 0;

    // Nếu bàn Cafe (giá 0đ) thì originalTimeMoney tự động bằng 0
    $subTotal = $originalTimeMoney + $serviceMoney;
    $displayDiscount = $subTotal - $finalTotal;

    // Lấy tên bàn (Hỗ trợ cả quan hệ table và bidaTable)
    $tableName = $session->table->name ?? $session->bidaTable->name ?? 'Mang về';
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
<table>
    <thead>
    <tr>
        <th style="width:50%">Tên món</th>
        <th style="width:15%; text-align: center;">SL</th>
        <th style="width:35%" class="text-right">Thành tiền</th>
    </tr>
    </thead>

    <tbody>

    {{-- 🔥 LOGIC MỚI: CHỈ HIỆN TIỀN GIỜ NẾU > 0 --}}
    @if($originalTimeMoney > 0)
        <tr>
            <td>
                <strong>Tiền giờ chơi</strong>
                <div style="font-size: 10px; color: #555; margin-top: 2px;">
                    ({{ $hours > 0 ? $hours.'h' : '' }}{{ $remainMinutes }}p)
                </div>
            </td>
            <td style="text-align: center;">1</td>
            <td class="text-right bold">{{ number_format($originalTimeMoney) }}</td>
        </tr>
    @endif
    {{-- 🔥 HẾT LOGIC --}}

    {{-- DANH SÁCH MÓN ĂN / NƯỚC --}}
    @foreach($session->orderItems as $item)
        <tr>
            <td>{{ $item->product->name }}</td>
            <td style="text-align: center;">{{ $item->quantity }}</td>
            <td class="text-right">{{ number_format($item->total) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="line"></div>

{{-- ================= TỔNG KẾT ================= --}}
<div class="text-right">
    <p style="margin: 4px 0;">Tổng cộng: <strong>{{ number_format($subTotal) }}</strong></p>

    {{-- GIẢM GIÁ --}}
    @if($displayDiscount > 0)
        <p style="margin: 4px 0; color: #333;">
            Giảm giá
            @if($session->discount_percent > 0)
                ({{ $session->discount_percent }}%)
            @endif:
            -{{ number_format($displayDiscount) }}
        </p>
        @if($session->note)
            <p style="font-size: 10px; font-style: italic; margin: 0;">({{ $session->note }})</p>
        @endif
        <div
            style="border-bottom: 1px dotted #000; width: 60%; margin-left: auto; margin-top: 4px; margin-bottom: 4px;"></div>
    @endif

    {{-- LÀM TRÒN (Nếu bạn có dùng logic làm tròn ở các bước trước) --}}
    @if($session->rounding_amount != 0)
        <p style="margin: 4px 0; font-style: italic; font-size: 11px;">
            Làm tròn: {{ $session->rounding_amount > 0 ? '+' : '' }}{{ number_format($session->rounding_amount) }}
        </p>
    @endif

    <p class="bold" style="font-size:16px; margin-top: 8px;">
        KHÁCH TRẢ: {{ number_format($finalTotal) }} đ
    </p>
</div>

{{-- ================= THANH TOÁN QR/CASH ================= --}}
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
            <p class="bold" style="margin: 5px 0 0 0; font-size: 11px;">QUÉT MÃ THANH TOÁN</p>
        </div>
    @else
        <div class="cash-box">
            <h3 style="margin: 0; font-size: 14px;">ĐÃ THANH TOÁN</h3>
        </div>
    @endif
@endif

{{-- ================= FOOTER ================= --}}
<div class="text-center" style="margin-top:15px; border-top: 1px dashed #000; padding-top: 10px;">
    <p style="font-size: 11px; margin: 0;">Wifi: {{ $setting->wifi_pass ?? 'Không có' }}</p>
    <p style="font-size: 11px; margin-top: 4px; font-style: italic;">Cảm ơn quý khách!</p>
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
