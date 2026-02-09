<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Filament\Resources\GameSessions\GameSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use App\Services\TableManagementService;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use App\Models\GameSession;
use App\Models\Table;

class EditGameSession extends EditRecord
{

    protected static string $resource = GameSessionResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            // ========== ACTION: MERGE ==========
            Action::make('merge')
                ->label('🔗 Ghép hóa đơn')
                ->icon('heroicon-m-arrow-right-left')
                ->color('info')
                ->visible(fn($record) => $record && $record->status === 'running')
                ->action(fn($record) => $this->showMergeModal($record))
                ->form([
                    Select::make('target_session_id')
                        ->label('Chọn hóa đơn để ghép vào')
                        ->options(function ($record) {
                            return GameSession::where('status', 'running')
                                ->where('id', '!=', $record->id)
                                ->get()
                                ->mapWithKeys(fn($session) => [
                                    $session->id => "#{$session->id} - Bàn: {$session->bidaTable?->name} (Giờ: {$session->start_time->format('H:i')})"
                                ]);
                        })
                        ->searchable()
                        ->required(),
                ])
                ->action(function ($record, array $data) {
                    $service = new TableManagementService();
                    try {
                        $targetSession = GameSession::findOrFail($data['target_session_id']);
                        $service->mergeSession($record, $targetSession);
                        
                        Notification::make()
                            ->title('✅ Ghép hóa đơn thành công')
                            ->body("Hóa đơn #{$record->id} đã được ghép vào hóa đơn #{$targetSession->id}")
                            ->success()
                            ->send();

                        $this->redirect(GameSessionResource::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Lỗi')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // ========== ACTION: TRANSFER TABLE ==========
            Action::make('transferTable')
                ->label('🔄 Đổi bàn')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->visible(fn($record) => $record && $record->status === 'running')
                ->form([
                    Select::make('new_table_id')
                        ->label('Chọn bàn mới')
                        ->options(function ($record) {
                            return Table::where('is_active', true)
                                ->where('id', '!=', $record->table_id)
                                ->get()
                                ->mapWithKeys(fn($table) => [
                                    $table->id => "{$table->name} - {$table->tableType?->name}"
                                ]);
                        })
                        ->searchable()
                        ->required(),
                    Textarea::make('reason')
                        ->label('Lý do đổi bàn (không bắt buộc)')
                        ->placeholder('vd: Bàn cũ bị hỏng, bàn quạt không chạy...')
                        ->rows(3),
                ])
                ->action(function ($record, array $data) {
                    $service = new TableManagementService();
                    try {
                        $newSession = $service->transferTableSession(
                            $record,
                            $data['new_table_id'],
                            $data['reason'] ?? ''
                        );
                        
                        Notification::make()
                            ->title('✅ Đổi bàn thành công')
                            ->body("Chuyển từ {$record->bidaTable?->name} sang bàn #{$newSession->bidaTable?->name}")
                            ->success()
                            ->send();

                        $this->redirect(GameSessionResource::getUrl('index'));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Lỗi')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // ========== ACTION: TOGGLE PAUSE ==========
            Action::make('togglePause')
                ->label(fn($record) => $record->isPaused() ? '▶️ Tiếp tục' : '⏸️ Tạm dừng')
                ->color(fn($record) => $record->isPaused() ? 'success' : 'warning')
                ->icon(fn($record) => $record->isPaused() ? 'heroicon-m-play' : 'heroicon-m-pause')
                ->visible(fn($record) => $record && $record->status === 'running')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $service = new TableManagementService();
                    try {
                        if ($record->isPaused()) {
                            $service->resumeSession($record);
                            Notification::make()
                                ->title('✅ Tiếp tục thành công')
                                ->body('Bàn ' . $record->bidaTable?->name . ' đã tiếp tục chạy')
                                ->success()
                                ->send();
                        } else {
                            $service->pauseSession($record);
                            Notification::make()
                                ->title('⏸️ Tạm dừng thành công')
                                ->body('Bàn ' . $record->bidaTable?->name . ' đã tạm dừng')
                                ->warning()
                                ->send();
                        }
                        $this->refreshFormData(['paused_at']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Lỗi')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }

    private function showMergeModal($record)
    {
        // Logic được xử lý trong form() callback
    }

}
