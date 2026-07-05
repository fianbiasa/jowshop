<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\PaymentReminder;

class SendPaymentReminders
{
    /**
     * Sends up to 3 reminders to customers with unpaid orders, roughly
     * 24h/48h/72h after the order was placed. Meant to be run hourly via
     * the scheduler (see app/Console/Commands/SendPaymentReminders.php).
     *
     * @return int number of reminders sent
     */
    public function __invoke(): int
    {
        $dueOrders = Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('payment_reminder_count', '<', 3)
            ->with('customer')
            ->get()
            ->filter(fn (Order $order) => $this->isDue($order));

        foreach ($dueOrders as $order) {
            $reminderNumber = $order->payment_reminder_count + 1;

            $order->customer->notify(new PaymentReminder($order, $reminderNumber));

            $order->update([
                'payment_reminder_count' => $reminderNumber,
                'last_payment_reminder_at' => now(),
            ]);
        }

        return $dueOrders->count();
    }

    private function isDue(Order $order): bool
    {
        $nextReminderNumber = $order->payment_reminder_count + 1;
        $dueAfterHours = $nextReminderNumber * 24;

        return $order->created_at->diffInHours(now()) >= $dueAfterHours;
    }
}
