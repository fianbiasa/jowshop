<?php

namespace Tests\Feature;

use App\Actions\SendPaymentReminders;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\PaymentReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentReminderTest extends TestCase
{
    use RefreshDatabase;

    private function pendingOrder(int $hoursOld, int $reminderCount = 0): Order
    {
        return Order::factory()->create([
            'status' => OrderStatus::Pending,
            'payment_reminder_count' => $reminderCount,
            'created_at' => now()->subHours($hoursOld),
        ]);
    }

    public function test_no_reminder_before_24_hours(): void
    {
        Notification::fake();

        $order = $this->pendingOrder(hoursOld: 10);

        (new SendPaymentReminders)();

        Notification::assertNothingSent();
        $this->assertSame(0, $order->fresh()->payment_reminder_count);
    }

    public function test_first_reminder_sent_after_24_hours(): void
    {
        Notification::fake();

        $order = $this->pendingOrder(hoursOld: 25);

        $sent = (new SendPaymentReminders)();

        $this->assertSame(1, $sent);
        Notification::assertSentTo(
            $order->customer,
            PaymentReminder::class,
            fn (PaymentReminder $notification) => $notification->reminderNumber === 1,
        );

        $order->refresh();
        $this->assertSame(1, $order->payment_reminder_count);
        $this->assertNotNull($order->last_payment_reminder_at);
    }

    public function test_second_reminder_is_not_sent_before_48_hours(): void
    {
        Notification::fake();

        $this->pendingOrder(hoursOld: 30, reminderCount: 1);

        (new SendPaymentReminders)();

        Notification::assertNothingSent();
    }

    public function test_second_reminder_sent_after_48_hours(): void
    {
        Notification::fake();

        $order = $this->pendingOrder(hoursOld: 49, reminderCount: 1);

        (new SendPaymentReminders)();

        Notification::assertSentTo(
            $order->customer,
            PaymentReminder::class,
            fn (PaymentReminder $notification) => $notification->reminderNumber === 2,
        );
        $this->assertSame(2, $order->fresh()->payment_reminder_count);
    }

    public function test_third_and_final_reminder_sent_after_72_hours_then_stops(): void
    {
        Notification::fake();

        $order = $this->pendingOrder(hoursOld: 73, reminderCount: 2);

        (new SendPaymentReminders)();

        Notification::assertSentTo(
            $order->customer,
            PaymentReminder::class,
            fn (PaymentReminder $notification) => $notification->reminderNumber === 3,
        );
        $this->assertSame(3, $order->fresh()->payment_reminder_count);

        // Running it again should not send a 4th reminder.
        Notification::fake();
        $sent = (new SendPaymentReminders)();

        $this->assertSame(0, $sent);
        Notification::assertNothingSent();
    }

    public function test_paid_orders_are_skipped(): void
    {
        Notification::fake();

        Order::factory()->create([
            'status' => OrderStatus::Paid,
            'created_at' => now()->subHours(100),
        ]);

        (new SendPaymentReminders)();

        Notification::assertNothingSent();
    }
}
