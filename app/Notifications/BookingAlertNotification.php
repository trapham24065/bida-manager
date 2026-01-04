<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingAlertNotification extends Notification
{

    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $level = 'warning'
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $notification = FilamentNotification::make()
            ->title($this->title)
            ->body($this->body);

        // Set màu sắc dựa trên level
        match ($this->level) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            'info' => $notification->info(),
            default => $notification->warning(),
        };

        return $notification->getDatabaseMessage();
    }
}
