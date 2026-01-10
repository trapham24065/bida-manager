@php
    $record = $getRecord();
    $session = $record->currentSession;
    $isActive = $session !== null;
@endphp
<div class="flex items-center gap-4">
    {{-- Kiểm tra qua quan hệ tableType --}}
    @if($getRecord()->tableType->category === 'cafe')
        {{-- Giao diện Cafe --}}
        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
            ☕
        </div>
    @else
        {{-- Giao diện Bida --}}
        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
            🎱
        </div>
    @endif
    <div class="relative h-52 rounded-xl border-4
    {{ $isActive ? 'border-yellow-500' : 'border-gray-700' }}
    bg-gradient-to-br from-amber-900 to-amber-700
    shadow-xl overflow-hidden">

        {{-- Đèn --}}
        <div class="absolute -top-6 left-1/2 -translate-x-1/2">
            <div class="w-1 h-6 bg-black mx-auto"></div>
            <div class="w-24 h-12 rounded-b-xl
            {{ $isActive ? 'bg-yellow-300 shadow-[0_0_30px_rgba(253,224,71,0.8)]' : 'bg-gray-700' }}">
            </div>
        </div>

        {{-- Bàn --}}
        <div class="mt-8 mx-4 h-32 rounded-lg border-4 border-black
        {{ $isActive ? 'bg-green-600' : 'bg-green-900' }}
        flex flex-col items-center justify-center">

            <h3 class="text-white font-black text-lg">
                {{ $record->name }}
            </h3>

            <p class="text-xs text-white/70">
                {{ strtoupper($record->type) }} · {{ number_format($record->price_per_hour / 1000) }}k/h
            </p>

            @if($isActive)
                <p class="text-yellow-300 text-xs mt-1 animate-pulse">
                    ⏱ {{ $session->start_time->format('H:i') }}
                </p>
            @else
                <p class="text-gray-300 text-xs mt-1">
                    TRỐNG
                </p>
            @endif
        </div>

        {{-- ACTIONS – NẰM GỌN TRONG THẺ --}}
        <div class="absolute bottom-2 inset-x-2 flex flex-wrap justify-center gap-1">

            {{-- Filament sẽ tự render actions --}}
        </div>
    </div>
</div>
