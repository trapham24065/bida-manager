<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Thông báo chế độ chỉnh sửa --}}
        @if($isEditMode)
        <div class="bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200 px-4 py-2 rounded-lg flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
            </svg>
            <span>Đang ở chế độ chỉnh sửa - Kéo thả các bàn để thay đổi vị trí</span>
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
                tableWidth: 100,
                tableHeight: 60,

                getAllTables() {
                    return Array.from(document.querySelectorAll('.table-item'));
                },

                checkCollision(x, y, excludeEl) {
                    const tables = this.getAllTables();
                    for (let table of tables) {
                        if (table === excludeEl) continue;
                        const left = parseInt(table.style.left) || 0;
                        const top = parseInt(table.style.top) || 0;
                        const right = left + this.tableWidth;
                        const bottom = top + this.tableHeight;

                        const newRight = x + this.tableWidth;
                        const newBottom = y + this.tableHeight;

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
                    let x = e.clientX - rect.left - this.offset.x;
                    let y = e.clientY - rect.top - this.offset.y;
                    x = Math.max(0, Math.min(x, container.offsetWidth - this.tableWidth));
                    y = Math.max(0, Math.min(y, container.offsetHeight - this.tableHeight));
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
            style="position: relative; min-height: 600px; background-image: radial-gradient(circle, #d1d5db 1px, transparent 1px); background-size: 20px 20px;"
            class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 border-2 border-dashed border-gray-300 dark:border-gray-600 overflow-hidden">
            @foreach($this->getViewData()['tables'] as $table)
            @php
            $isPlaying = $table->currentSession !== null;
            $bgColor = $isPlaying ? '#dc2626' : '#16a34a';
            $borderColor = $isPlaying ? '#b91c1c' : '#15803d';
            $posX = $table->position_x ?? 0;
            $posY = $table->position_y ?? 0;
            @endphp
            <div
                wire:key="table-{{ $table->id }}"
                class="table-item"
                data-table-id="{{ $table->id }}"
                data-orig-x="{{ $posX }}"
                data-orig-y="{{ $posY }}"
                @mousedown="startDrag($event, $el)"
                x-bind:class="isEditMode ? 'cursor-grab' : 'cursor-pointer'"
                style="position: absolute; width: 100px; height: 60px; left: {{ $posX }}px; top: {{ $posY }}px;"
                title="{{ $table->name }} - {{ $table->tableType?->name ?? 'Không có loại' }}">
                <div
                    class="table-inner"
                    style="width: 100%; height: 100%; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: {{ $bgColor }}; border: 3px solid {{ $borderColor }}; color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); overflow: hidden;">
                    <div style="font-weight: bold; font-size: 11px; text-align: center; padding: 0 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                        {{ $table->name }}
                    </div>
                    <div style="font-size: 10px; opacity: 0.9; display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                        @if($isPlaying)
                        <span style="width: 6px; height: 6px; background: white; border-radius: 50%; animation: pulse 2s infinite;"></span>
                        <span>Đang chơi</span>
                        @else
                        <span style="width: 6px; height: 6px; background: rgba(255,255,255,0.7); border-radius: 50%;"></span>
                        <span>Trống</span>
                        @endif
                    </div>
                </div>
                @if($table->tableType)
                <div style="position: absolute; top: -6px; right: -6px; background: #1f2937; color: white; font-size: 9px; padding: 1px 5px; border-radius: 9999px; box-shadow: 0 1px 2px rgba(0,0,0,0.2); max-width: 60px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $table->tableType->name }}">
                    {{ $table->tableType->name }}
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- CHÚ THÍCH --}}
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <div class="rounded" style="width: 24px; height: 16px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: 2px solid #15803d;"></div>
                <span class="text-gray-600 dark:text-gray-400">Bàn trống</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="rounded" style="width: 24px; height: 16px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: 2px solid #b91c1c;"></div>
                <span class="text-gray-600 dark:text-gray-400">Đang chơi</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="rounded-full animate-pulse" style="width: 8px; height: 8px; background: white; border: 1px solid #d1d5db;"></div>
                <span class="text-gray-600 dark:text-gray-400">Đang hoạt động</span>
            </div>
        </div>

    </div>
</x-filament-panels::page>