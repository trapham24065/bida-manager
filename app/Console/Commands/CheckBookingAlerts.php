<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingAlertNotification;
use Carbon\Carbon;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CheckBookingAlerts extends Command
{

    protected $signature = 'bookings:check-alerts';

    protected $description = 'Send upcoming and late booking alerts';

    public function handle(): int
    {
        $now = Carbon::now();

        // Lấy admin + staff
        $users = User::whereIn('role', ['admin', 'staff'])->get();

        if ($users->isEmpty()) {
            $this->warn('No users to notify.');
            return CommandAlias::SUCCESS;
        }

        /**
         * 1️⃣ Booking sắp tới
         */
        $upcomingBookings = Booking::query()
            ->where('is_reminded_upcoming', false)
            ->whereBetween('booking_time', [
                $now,
                $now->copy()->addMinutes(15),
            ])
            ->get();

        foreach ($upcomingBookings as $booking) {
            foreach ($users as $user) {
                $user->notify(
                    new BookingAlertNotification(
                        '⏰ Sắp đến giờ đặt bàn',
                        "Khách {$booking->customer_name} - bàn {$booking->bidaTable?->name} lúc {$booking->booking_time->format('H:i')}",
                        'warning'
                    )
                );
            }

            $booking->update(['is_reminded_upcoming' => true]);
        }

        /**
         * 2️⃣ Booking trễ
         */
        $lateBookings = Booking::query()
            ->where('is_reminded_late', false)
            ->where('booking_time', '<', $now)
            ->get();

        foreach ($lateBookings as $booking) {
            foreach ($users as $user) {
                $user->notify(
                    new BookingAlertNotification(
                        '⚠️ Đặt bàn đã trễ',
                        "Khách {$booking->customer_name} - bàn {$booking->bidaTable?->name} đã quá giờ đặt",
                        'danger'
                    )
                );
            }

            $booking->update(['is_reminded_late' => true]);
        }

        $this->info('Booking alerts sent successfully.');

        return Command::SUCCESS;
    }

}
