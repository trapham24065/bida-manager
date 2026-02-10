<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GameSession;
use App\Models\User;
use App\Notifications\BookingAlertNotification;
use Carbon\Carbon;

class ProcessScheduledPauses extends Command
{
    protected $signature = 'sessions:process-scheduled-pauses';

    protected $description = 'Pause game sessions that reached their scheduled end and notify staff';

    public function handle(): int
    {
        $now = Carbon::now();

        // Find active sessions with schedule set and scheduled_until <= now
        $sessions = GameSession::query()
            ->where('scheduled_auto_pause', true)
            ->whereNotNull('scheduled_until')
            ->where('scheduled_until', '<=', $now)
            ->whereNull('end_time')
            ->whereNull('paused_at')
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('No scheduled sessions to pause.');
            return Command::SUCCESS;
        }

        $users = User::whereIn('role', ['admin', 'staff'])->get();

        foreach ($sessions as $session) {
            // Pause the session
            $session->pause();

            // Notify staff
            foreach ($users as $user) {
                $user->notify(new BookingAlertNotification(
                    '⏸️ Auto-pause bàn',
                    "Bàn {$session->bidaTable?->name} đã tự động tạm dừng sau {$session->scheduled_minutes} phút",
                    'warning'
                ));
            }

            // Clear schedule flag (optional) to avoid repeated triggers
            $session->clearSchedule();
        }

        $this->info('Processed '. $sessions->count() .' scheduled pauses.');

        return Command::SUCCESS;
    }

}
