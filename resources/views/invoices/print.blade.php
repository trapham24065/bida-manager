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
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; }
        td { padding: 4px 0; vertical-align: top; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>

<body onload="window.print()">

@php
    use Carbon\Carbon;

    $setting = \App\Models\ShopSetting::first();

    // 1. TÍNH LẠI THỜI GIAN CHƠI CHÍNH XÁC
    $start = Carbon::parse($session->start_time);
    $end   = Carbon::parse($session->end_time);

    $seconds = $end->diffInSeconds($start);
    $minutes = max(1, (int) ceil($seconds / 60)); // Làm tròn phút

    $hours = intdiv($minutes, 60);
    $remainMinutes = $minutes % 60;

    // 2. TÍNH TIỀN GIỜ GỐC (Tính lại từ đầu thay vì suy ngược)
    // Cần copy lại logic tính tiền giờ đơn giản hoặc lấy giá trung bình
    // Tuy nhiên, để chính xác nhất mà không cần query lại Rules phức tạp:
    // Ta lấy: Tổng tiền cuối (trong DB) + Tiền giảm (trong DB) - Tiền món = Tiền giờ
    // NHƯNG ĐỂ TRÁNH LỖI "GIẢM 200k", TA SẼ LÀM NHƯ SAU:

    // A. Lấy tiền dịch vụ
    $serviceMoney = $session->orderItems->sum('total');

    // B. Tính tiền giờ (Giả lập lại logic tính giá - Cách an toàn nhất là lấy từ subtotal nếu có lưu, nhưng ta không lưu subtotal)
    // Mẹo: Vì ta không muốn copy lại 100 dòng logic tính tiền vào Blade, ta sẽ dùng Logic:
    // Tổng Gốc Thực Tế = (Tiền trả + Giảm giá trong DB)
    // Nếu Giảm giá > Tổng gốc (trường hợp lỗi cũ) -> Phải Cắt Bớt.

    $finalTotal = $session->total_money; // Khách trả 0đ
    $storedDiscount = $session->discount_amount; // DB lưu 200k

    // Tổng gốc ảo (theo DB cũ bị lỗi) = 0 + 200k = 200k -> SAI
    // Vì vậy, ở đây ta chỉ có thể hiển thị đúng nếu BƯỚC 1 (Validate) đã được áp dụng.

    // TUY NHIÊN, ĐỂ HIỂN THỊ HỢP LÝ CHO CÁC ĐƠN LỖI:
    // Ta sẽ tính lại Tiền Giờ theo giá mặc định (ước lượng) để hiển thị không bị số 0
    // Hoặc chấp nhận hiển thị theo DB nhưng ghi chú.

    // CÁCH TỐT NHẤT: TÍNH XUÔI
    $tempSubTotal = $finalTotal;

    if ($session->discount_percent > 0) {
        // Nếu giảm % thì tính ngược lại được
        if ($session->discount_percent < 100) {
             $tempSubTotal = $finalTotal / (1 - ($session->discount_percent / 100));
        }
    } else {
        $tempSubTotal = $finalTotal + $session->discount_amount;
    }

    // Logic hiển thị an toàn:
    // Tiền giờ = Tổng (đã cộng lại giảm giá) - Tiền món
    $originalTimeMoney = $tempSubTotal - $serviceMoney;

    // Nếu Tiền giờ bị Âm (do nhập giảm giá lố bịch), ép về 0
    if ($originalTimeMoney < 0) $originalTimeMoney = 0;

    // Tính lại Tổng gốc chuẩn để hiển thị
    $subTotal = $originalTimeMoney + $serviceMoney;

    // Tính lại Tiền giảm giá hiển thị (để khớp con số)
    // Discount hiển thị = Tổng gốc - Khách trả
    $displayDiscount = $subTotal - $finalTotal;

@endphp

{{-- ================= HEADER ================= --}}
<div class="text-center">
    <h2 style="margin-bottom:5px;text-transform:uppercase;">
        {{ $setting->shop_name ?? 'CLB BIDA' }}
    </h2>
    @if($setting?->address)
        <p style="font-size:12px;margin:2px 0;">ĐC: {{ $setting->address }}</p>
    @endif
    @if($setting?->phone)
        <p style="font-size:12px;margin:2px 0;">SĐT: {{ $setting->phone }}</p>
    @endif
</div>

<div class="line"></div>

{{-- ================= INFO ================= --}}
<div>
    <table style="font-size: 13px;">
        <tr>
            <td>Hóa đơn: <strong>#{{ $session->id }}</strong></td>
            <td class="text-right">Bàn: <strong>{{ $session->bidaTable->name }}</strong></td>
        </tr>
        <tr>
            <td>Vào: {{ $start->format('H:i') }}</td>
            <td class="text-right">Ra: {{ $end->format('H:i') }}</td>
        </tr>
        <tr>
            <td colspan="2">Ngày: {{ $end->format('d/m/Y') }}</td>
        </tr>
    </table>
</div>

<div class="line"></div>

{{-- ================= CHI TIẾT ================= --}}
<table>
    <thead>
    <tr style="border-bottom: 1px solid #ddd;">
        <th style="width:45%">Tên món</th>
        <th style="width:15%">SL</th>
        <th style="width:40%" class="text-right">Thành tiền</th>
    </tr>
    </thead>

    <tbody>
    {{-- 1. DÒNG TIỀN GIỜ (HIỂN THỊ GIÁ GỐC) --}}
    <tr>
        <td>
            <strong>Tiền giờ</strong>
            <div style="font-size: 11px; color: #555;">
                {{ $hours > 0 ? $hours.'h ' : '' }}{{ $remainMinutes }}p
            </div>
        </td>
        <td>1</td>
        <td class="text-right bold">{{ number_format($originalTimeMoney) }}</td>
    </tr>

    {{-- 2. CÁC MÓN ĐÃ GỌI --}}
    @foreach($session->orderItems as $item)
        <tr>
            <td>{{ $item->product->name }}</td>
            <td>{{ $item->quantity }}</td>
            <td class="text-right">{{ number_format($item->total) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="line"></div>

{{-- ================= TỔNG KẾT TIỀN ================= --}}
<div class="text-right">
    {{-- 1. Tổng tiền hàng (Subtotal) --}}
    <p style="margin: 5px 0;">Tổng tiền hàng: <strong>{{ number_format($subTotal) }} đ</strong></p>

    {{-- 2. Dòng giảm giá (Chỉ hiện nếu có giảm) --}}
    @if($displayDiscount > 0)
        <p style="margin: 5px 0; color: #444; font-style: italic;">
            Giảm giá
            @if($session->discount_percent > 0)
                ({{ $session->discount_percent }}%)
            @endif:
            -{{ number_format($displayDiscount) }} đ
        </p>
        @if($session->note)
            <p style="font-size: 11px; color: #666; font-style: italic; margin-bottom: 5px;">(Lý
                do: {{ $session->note }})</p>
        @endif
        <div style="border-bottom: 1px solid #000; width: 50%; margin-left: auto; margin-bottom: 5px;"></div>
    @endif

    {{-- 3. Tổng thanh toán cuối cùng (Final Total) --}}
    <p class="bold" style="font-size:18px; margin-top: 10px;">
        THANH TOÁN: {{ number_format($finalTotal) }} đ
    </p>
</div>

{{-- ================= QR CODE ================= --}}
@if($setting && $setting->bank_account && $finalTotal > 0)
    @php
        $qrUrl = "https://img.vietqr.io/image/{$setting->bank_id}-{$setting->bank_account}-qr_only.png"
            ."?amount={$finalTotal}"  // Dùng số tiền cuối cùng sau giảm giá
            ."&addInfo=HD{$session->id}"
            ."&accountName={$setting->bank_account_name}";
    @endphp

    <div class="text-center" style="margin-top:15px;">
        <img src="{{ $qrUrl }}" style="width:150px; height:150px;">
        <p style="font-size:11px; margin-top:5px;">Quét mã để thanh toán</p>
    </div>
@endif

{{-- ================= FOOTER ================= --}}
<div class="text-center" style="margin-top:20px;">
    <p style="font-size: 12px;"><i>Cảm ơn quý khách & Hẹn gặp lại!</i></p>
    @if($setting->wifi_pass)
        <p style="font-size: 12px; border: 1px dashed #333; display: inline-block; padding: 5px 10px; margin-top: 5px;">
            Wifi: {{ $setting->wifi_pass }}
        </p>
    @endif
</div>

<div class="no-print text-center" style="margin-top:30px; margin-bottom: 50px;">
    <a href="/admin/tables"
       style="padding:12px 25px; background:#222; color:#fff; border-radius:6px; text-decoration:none; font-weight: bold;">
        ⬅ Quay lại Trang chủ
    </a>
    <button onclick="window.print()"
            style="padding:12px 25px; background:#007bff; color:#fff; border:none; border-radius:6px; font-weight: bold; cursor: pointer; margin-left: 10px;">
        🖨 In hóa đơn
    </button>
</div>

</body>
</html>
