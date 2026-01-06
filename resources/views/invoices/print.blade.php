<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn #{{ $session->id }}</title>

    <style>
        @page {
            size: auto; /* Khổ giấy in nhiệt K80 */
            margin: 0;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            width: 72mm; /* Trừ lề an toàn cho máy in */
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

        /* CSS cho 2 trường hợp thanh toán */
        .qr-box { margin-top: 10px; text-align: center; }
        .cash-box {
            border: 2px solid #000;
            padding: 8px;
            margin: 10px 10px;
            text-align: center;
            border-radius: 4px;
        }

        /* Ẩn nút in khi in ra giấy */
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>

<body onload="window.print()">

@php
    use Carbon\Carbon;

    // Lấy thông tin quán
    $setting = \App\Models\ShopSetting::first();

    // 1. TÍNH THỜI GIAN CHƠI
    $start = Carbon::parse($session->start_time);
    $end   = $session->end_time ? Carbon::parse($session->end_time) : now();

    $seconds = $end->diffInSeconds($start);
    $minutes = max(1, (int) ceil($seconds / 60)); // Làm tròn phút

    $hours = intdiv($minutes, 60);
    $remainMinutes = $minutes % 60;

    // 2. TÍNH TOÁN TIỀN HIỂN THỊ
    $finalTotal = $session->total_money; // Khách phải trả
    $serviceMoney = $session->orderItems->sum('total'); // Tiền nước

    // Tính ngược lại Tổng gốc (SubTotal) để hiển thị cho khớp
    $tempSubTotal = $finalTotal;

    if ($session->discount_percent > 0 && $session->discount_percent < 100) {
         $tempSubTotal = $finalTotal / (1 - ($session->discount_percent / 100));
    } else {
        $tempSubTotal = $finalTotal + ($session->discount_amount ?? 0);
    }

    // Tiền giờ = Tổng gốc - Tiền nước
    $originalTimeMoney = $tempSubTotal - $serviceMoney;
    if ($originalTimeMoney < 0) $originalTimeMoney = 0;

    // Tổng gốc chuẩn
    $subTotal = $originalTimeMoney + $serviceMoney;

    // Tiền giảm giá hiển thị
    $displayDiscount = $subTotal - $finalTotal;
@endphp

{{-- ================= HEADER QUÁN ================= --}}
<div class="text-center">
    <h2 class="uppercase" style="margin: 5px 0; font-size: 16px;">
        {{ $setting->shop_name ?? 'BIDA CLUB' }}
    </h2>
    @if($setting?->address)
        <p style="margin:2px 0;">ĐC: {{ $setting->address }}</p>
    @endif
    @if($setting?->phone)
        <p style="margin:2px 0;">SĐT: {{ $setting->phone }}</p>
    @endif
</div>

<div class="line"></div>

{{-- ================= THÔNG TIN PHIẾU ================= --}}
<div>
    <table>
        <tr>
            <td>HĐ: <strong>#{{ $session->id }}</strong></td>
            <td class="text-right">Bàn: <strong
                    style="font-size: 14px;">{!! $session->bidaTable->name ?? 'Bàn ?' !!}</strong>
            </td>
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

{{-- ================= CHI TIẾT MÓN ================= --}}
<table>
    <thead>
    <tr>
        <th style="width:50%">Tên món/DV</th>
        <th style="width:15%; text-align: center;">SL</th>
        <th style="width:35%" class="text-right">Thành tiền</th>
    </tr>
    </thead>

    <tbody>
    {{-- 1. TIỀN GIỜ --}}
    <tr>
        <td>
            <strong>Tiền giờ</strong>
            <div style="font-size: 10px; color: #555; margin-top: 2px;">
                ({{ $hours > 0 ? $hours.'h' : '' }}{{ $remainMinutes }}p)
            </div>
        </td>
        <td style="text-align: center;">1</td>
        <td class="text-right bold">{{ number_format($originalTimeMoney) }}</td>
    </tr>

    {{-- 2. DỊCH VỤ / ĐỒ UỐNG --}}
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

{{-- ================= TỔNG KẾT TIỀN ================= --}}
<div class="text-right">
    <p style="margin: 4px 0;">Tổng cộng: <strong>{{ number_format($subTotal) }}</strong></p>

    {{-- Chỉ hiện giảm giá nếu có --}}
    @if($displayDiscount > 0)
        <p style="margin: 4px 0; color: #333;">
            Giảm giá
            @if($session->discount_percent > 0)
                ({{ $session->discount_percent }}%)
            @endif:
            -{{ number_format($displayDiscount) }}
        </p>
        @if($session->note)
            <p style="font-size: 10px; font-style: italic; margin: 0;">(Lý do: {{ $session->note }})</p>
        @endif
        <div
            style="border-bottom: 1px dotted #000; width: 60%; margin-left: auto; margin-top: 4px; margin-bottom: 4px;"></div>
    @endif

    <p class="bold" style="font-size:16px; margin-top: 8px;">
        KHÁCH TRẢ: {{ number_format($finalTotal) }} đ
    </p>
</div>

{{-- ================= LOGIC QUAN TRỌNG: QR HAY TIỀN MẶT ================= --}}
@if($finalTotal > 0)
    {{-- TRƯỜNG HỢP 1: CHUYỂN KHOẢN -> HIỆN QR CODE --}}
    @if($session->payment_method === 'transfer' && $setting && $setting->bank_account)
        @php
            // Link tạo mã QR VietQR tự động
            $qrUrl = "https://img.vietqr.io/image/{$setting->bank_id}-{$setting->bank_account}-qr_only.png"
                ."?amount={$finalTotal}"
                ."&addInfo=HD{$session->id} Ban{$session->table_id}"
                ."&accountName={$setting->bank_account_name}";
        @endphp

        <div class="qr-box">
            <img src="{{ $qrUrl }}" style="width:130px; height:130px; border: 1px solid #ddd;">
            <p class="bold" style="margin: 5px 0 0 0; font-size: 11px;">QUÉT MÃ ĐỂ THANH TOÁN</p>
            <p style="font-size: 10px; margin: 0;">{{ $setting->bank_account_name }}</p>
        </div>

        {{-- TRƯỜNG HỢP 2: TIỀN MẶT -> HIỆN KHUNG XÁC NHẬN --}}
    @else
        <div class="cash-box">
            <h3 style="margin: 0; font-size: 14px; font-weight: bold; text-transform: uppercase;">ĐÃ THANH TOÁN</h3>
            <p style="margin: 2px 0 0 0; font-size: 11px;">(Tiền mặt)</p>
        </div>
    @endif
@endif

{{-- ================= FOOTER ================= --}}
<div class="text-center" style="margin-top:15px; border-top: 1px dashed #000; padding-top: 10px;">
    <p style="font-size: 11px; margin: 0;">Cảm ơn quý khách & Hẹn gặp lại!</p>

    @if($setting && $setting->wifi_pass)
        <div style="margin-top: 8px; font-size: 11px;">
            <strong>Pass Wifi:</strong> {{ $setting->wifi_pass }}
        </div>
    @endif
</div>

{{-- Nút điều khiển (Không in ra giấy) --}}
<div class="no-print text-center"
     style="margin-top:30px; margin-bottom: 50px; padding-top: 20px; border-top: 1px solid #eee;">
    <a href="/admin/tables" style="color: #555; text-decoration: none; font-size: 13px; margin-right: 15px;">
        ⬅ Về trang chủ
    </a>
    <button onclick="window.print()"
            style="background: #000; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">
        🖨 IN HÓA ĐƠN
    </button>
</div>

</body>
</html>
