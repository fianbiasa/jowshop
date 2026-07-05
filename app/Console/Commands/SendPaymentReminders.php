<?php

namespace App\Console\Commands;

use App\Actions\SendPaymentReminders as SendPaymentRemindersAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-payment-reminders')]
#[Description('Send scarcity-driven reminder emails for unpaid orders (roughly 24h/48h/72h after checkout)')]
class SendPaymentReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SendPaymentRemindersAction $action): void
    {
        $count = $action();

        $this->info("Sent {$count} payment reminder(s).");
    }
}
