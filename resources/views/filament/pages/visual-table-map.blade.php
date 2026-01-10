<x-filament-panels::page>
    <style>
        /* ===== TABLE MAP STYLES ===== */
        #table-map {
            display: block !important;
            position: relative !important;
            min-height: 600px;
        }

        /* Table item - PHẢI CÓ position absolute */
        #table-map .table-item {
            position: absolute !important;
            display: block !important;
            box-sizing: border-box !important;
        }

        /* Table inner */
        #table-map .table-item .table-inner {
            width: 100% !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            box-sizing: border-box !important;
        }

        /* Action popup panel */
        #table-map .action-popup {
            position: absolute !important;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
            padding: 8px;
            min-width: 120px;
            z-index: 1000 !important;
        }

        .dark #table-map .action-popup {
            background: #1f2937;
            border-color: #374151;
        }

        /* Action buttons */
        #table-map .action-popup .action-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            color: white;
            margin-bottom: 4px;
        }

        #table-map .action-popup .action-btn:last-child {
            margin-bottom: 0;
        }

        #table-map .action-popup .action-btn svg {
            width: 14px !important;
            height: 14px !important;
            flex-shrink: 0;
        }

        #table-map .action-popup .action-btn.btn-green {
            background: #16a34a;
        }

        #table-map .action-popup .action-btn.btn-green:hover {
            background: #15803d;
        }

        #table-map .action-popup .action-btn.btn-amber {
            background: #d97706;
        }

        #table-map .action-popup .action-btn.btn-amber:hover {
            background: #b45309;
        }

        #table-map .action-popup .action-btn.btn-blue {
            background: #2563eb;
        }

        #table-map .action-popup .action-btn.btn-blue:hover {
            background: #1d4ed8;
        }

        #table-map .action-popup .action-btn.btn-red {
            background: #dc2626;
        }

        #table-map .action-popup .action-btn.btn-red:hover {
            background: #b91c1c;
        }

        /* Animation */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }
    </style>
    <div class="space-y-4" style="display: block !important;">

        {{-- Thông báo chế độ chỉnh sửa --}}
        @if($isEditMode)
        <div class="bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200 px-4 py-2 rounded-lg flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
            </svg>
            <span>Đang ở chế độ chỉnh sửa - Kéo thả các bàn để thay đổi vị trí</span>
        </div>
        @else
        <div class="bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 px-4 py-2 rounded-lg flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.591M6 10.5H3.75m4.007-4.243l-1.59-1.591" />
            </svg>
            <span>💡 Click vào bàn để thao tác nhanh (Bắt đầu, Gọi món, Tính tiền...)</span>
        </div>
        @endif

        {{-- MAP --}}
        <div
            id="table-map"
            wire:ignore.self
            x-data="{
                isDragging: false,
                currentTable: null,
                offset: { x: 0, y: 0 },
                isEditMode: @entangle('isEditMode'),
                selectedTable: null,

                // Lấy kích thước bàn dựa trên loại
                getTableSize(tableEl) {
                    const isCafe = tableEl.dataset.category === 'cafe';
                    return {
                        width: isCafe ? 70 : 100,
                        height: isCafe ? 70 : 60
                    };
                },

                getAllTables() {
                    return Array.from(document.querySelectorAll('.table-item'));
                },

                checkCollision(x, y, excludeEl) {
                    const tables = this.getAllTables();
                    const currentSize = this.getTableSize(excludeEl);

                    for (let table of tables) {
                        if (table === excludeEl) continue;
                        const tableSize = this.getTableSize(table);
                        const left = parseInt(table.style.left) || 0;
                        const top = parseInt(table.style.top) || 0;
                        const right = left + tableSize.width;
                        const bottom = top + tableSize.height;

                        const newRight = x + currentSize.width;
                        const newBottom = y + currentSize.height;

                        if (x < right && newRight > left && y < bottom && newBottom > top) {
                            return true;
                        }
                    }
                    return false;
                },

                startDrag(e, tableEl) {
                    if (!this.isEditMode) return;
                    this.isDragging = true;
                    this.currentTable = tableEl;
                    const rect = tableEl.getBoundingClientRect();
                    this.offset.x = e.clientX - rect.left;
                    this.offset.y = e.clientY - rect.top;
                    tableEl.style.zIndex = 1000;
                    tableEl.style.transform = 'scale(1.05)';
                    tableEl.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
                    tableEl.style.opacity = '0.9';
                    e.preventDefault();
                },

                drag(e) {
                    if (!this.isDragging || !this.currentTable) return;
                    const container = document.getElementById('table-map');
                    const rect = container.getBoundingClientRect();
                    const size = this.getTableSize(this.currentTable);
                    let x = e.clientX - rect.left - this.offset.x;
                    let y = e.clientY - rect.top - this.offset.y;
                    x = Math.max(0, Math.min(x, container.offsetWidth - size.width));
                    y = Math.max(0, Math.min(y, container.offsetHeight - size.height));
                    this.currentTable.style.left = x + 'px';
                    this.currentTable.style.top = y + 'px';

                    // Kiểm tra va chạm và đổi màu viền
                    if (this.checkCollision(x, y, this.currentTable)) {
                        this.currentTable.querySelector('.table-inner').style.outline = '3px solid #fbbf24';
                    } else {
                        this.currentTable.querySelector('.table-inner').style.outline = 'none';
                    }
                },

                endDrag() {
                    if (!this.isDragging || !this.currentTable) return;
                    const x = parseInt(this.currentTable.style.left) || 0;
                    const y = parseInt(this.currentTable.style.top) || 0;

                    // Nếu va chạm, hoàn tác về vị trí cũ
                    if (this.checkCollision(x, y, this.currentTable)) {
                        const tableId = this.currentTable.dataset.tableId;
                        const origX = this.currentTable.dataset.origX;
                        const origY = this.currentTable.dataset.origY;
                        this.currentTable.style.left = origX + 'px';
                        this.currentTable.style.top = origY + 'px';
                        this.currentTable.querySelector('.table-inner').style.outline = 'none';
                    } else {
                        const tableId = this.currentTable.dataset.tableId;
                        this.currentTable.dataset.origX = x;
                        this.currentTable.dataset.origY = y;
                        $wire.updateTablePosition(tableId, x, y);
                    }

                    this.currentTable.style.zIndex = '';
                    this.currentTable.style.transform = '';
                    this.currentTable.style.boxShadow = '';
                    this.currentTable.style.opacity = '';
                    this.isDragging = false;
                    this.currentTable = null;
                }
            }"
            @mousemove.window="drag($event)"
            @mouseup.window="endDrag()"
            @click.self="selectedTable = null"
            style="position: relative; min-height: 600px; background-image: radial-gradient(circle, #d1d5db 1px, transparent 1px); background-size: 20px 20px;"
            class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 overflow-hidden">
            @foreach($this->getViewData()['tables'] as $table)
            @php
            $isPlaying = $table->currentSession !== null;
            $isCafe = $table->tableType?->category === 'cafe';

            // Màu sắc theo loại bàn và trạng thái
            if ($isCafe) {
            // Bàn Cafe: màu cam/nâu cafe
            $bgColor = $isPlaying ? '#ea580c' : '#d97706'; // Orange khi có khách, Amber khi trống
            $borderColor = $isPlaying ? '#c2410c' : '#b45309';
            } else {
            // Bàn Bida: màu xanh/đỏ như cũ
            $bgColor = $isPlaying ? '#dc2626' : '#16a34a';
            $borderColor = $isPlaying ? '#b91c1c' : '#15803d';
            }

            $posX = $table->position_x ?? 0;
            $posY = $table->position_y ?? 0;

            // Kích thước và hình dạng theo loại
            $tableWidth = $isCafe ? 70 : 100;
            $tableHeight = $isCafe ? 70 : 60;
            $borderRadius = $isCafe ? '50%' : '8px';
            @endphp
            <div
                wire:key="table-{{ $table->id }}"
                class="table-item"
                data-table-id="{{ $table->id }}"
                data-orig-x="{{ $posX }}"
                data-orig-y="{{ $posY }}"
                data-category="{{ $isCafe ? 'cafe' : 'bida' }}"
                x-on:mousedown="startDrag($event, $el)"
                x-on:click.stop="if (!isEditMode) { selectedTable = selectedTable === {{ $table->id }} ? null : {{ $table->id }}; }"
                x-bind:class="{
                    'cursor-grab': isEditMode,
                    'cursor-pointer': !isEditMode,
                    'ring-4 ring-blue-500 ring-offset-2': selectedTable === {{ $table->id }}
                }"
                style="left: {{ $posX }}px; top: {{ $posY }}px; width: {{ $tableWidth }}px; height: {{ $tableHeight }}px;"
                title="{{ $table->name }} - {{ $table->tableType?->name ?? 'Không có loại' }}">
                <div
                    class="table-inner transition-transform duration-200"
                    :class="selectedTable === {{ $table->id }} ? 'scale-110' : 'group-hover:scale-105'"
                    style="width: 100%; height: 100%; border-radius: {{ $borderRadius }}; display: flex; flex-direction: column; align-items: center; justify-content: center; background: {{ $bgColor }}; border: 3px solid {{ $borderColor }}; color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); overflow: hidden;">

                    {{-- Icon theo loại bàn --}}
                    @if($isCafe)
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px; opacity: 0.9;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0012.016 15a4.486 4.486 0 00-3.198 1.318M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z" />
                    </svg>
                    @endif

                    <div style="font-weight: bold; font-size: {{ $isCafe ? '10px' : '11px' }}; text-align: center; padding: 0 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                        {{ $table->name }}
                    </div>
                    <div style="font-size: {{ $isCafe ? '9px' : '10px' }}; opacity: 0.9; display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                        @if($isPlaying)
                        <span style="width: 6px; height: 6px; background: white; border-radius: 50%; animation: pulse 2s infinite;"></span>
                        <span>{{ $isCafe ? 'Có khách' : 'Đang chơi' }}</span>
                        @else
                        <span style="width: 6px; height: 6px; background: rgba(255,255,255,0.7); border-radius: 50%;"></span>
                        <span>Trống</span>
                        @endif
                    </div>
                </div>

                {{-- Badge loại bàn --}}
                @if($table->tableType)
                <div style="position: absolute; top: -6px; right: -6px; background: {{ $isCafe ? '#7c2d12' : '#1f2937' }}; color: white; font-size: 9px; padding: 1px 5px; border-radius: 9999px; box-shadow: 0 1px 2px rgba(0,0,0,0.2); max-width: 60px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $table->tableType->name }}">
                    {{ $table->tableType->name }}
                </div>
                @endif

                {{-- ACTION PANEL (hiện khi click) --}}
                <div
                    x-show="selectedTable === {{ $table->id }} && !isEditMode"
                    x-cloak
                    x-on:click.stop
                    class="action-popup"
                    style="top: {{ $tableHeight + 10 }}px; left: 50%; transform: translateX(-50%);">

                    <div style="font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 6px; text-align: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px;">
                        {{ $table->name }}
                    </div>

                    @if(!$isPlaying)
                    {{-- Bàn trống: hiện nút Bắt đầu --}}
                    <button wire:click="mountAction('start', { table: {{ $table->id }} })" class="action-btn btn-green">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Bắt đầu
                    </button>
                    @else
                    {{-- Bàn đang chơi --}}
                    <button wire:click="mountAction('order', { table: {{ $table->id }} })" class="action-btn btn-amber">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Gọi món
                    </button>
                    <button wire:click="mountAction('viewSession', { table: {{ $table->id }} })" class="action-btn btn-blue">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Xem HĐ
                    </button>
                    <button wire:click="mountAction('stop', { table: {{ $table->id }} })" class="action-btn btn-red">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Tính tiền
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>



        {{-- CHÚ THÍCH --}}
        <div class="flex flex-wrap gap-6 text-sm">
            {{-- Phần Bida --}}
            <div class="flex items-center gap-3 px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Bàn Bida:</span>
                <div class="flex items-center gap-2">
                    <div class="rounded" style="width: 24px; height: 16px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: 2px solid #15803d;"></div>
                    <span class="text-gray-600 dark:text-gray-400">Trống</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="rounded" style="width: 24px; height: 16px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: 2px solid #b91c1c;"></div>
                    <span class="text-gray-600 dark:text-gray-400">Đang chơi</span>
                </div>
            </div>

            {{-- Phần Cafe --}}
            <div class="flex items-center gap-3 px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Bàn Cafe:</span>
                <div class="flex items-center gap-2">
                    <div class="rounded-full" style="width: 18px; height: 18px; background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); border: 2px solid #b45309;"></div>
                    <span class="text-gray-600 dark:text-gray-400">Trống</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="rounded-full" style="width: 18px; height: 18px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); border: 2px solid #c2410c;"></div>
                    <span class="text-gray-600 dark:text-gray-400">Có khách</span>
                </div>
            </div>

            {{-- Chỉ báo hoạt động --}}
            <div class="flex items-center gap-2">
                <div class="rounded-full animate-pulse" style="width: 8px; height: 8px; background: white; border: 1px solid #d1d5db;"></div>
                <span class="text-gray-600 dark:text-gray-400">Đang hoạt động</span>
            </div>
        </div>

    </div>
</x-filament-panels::page>