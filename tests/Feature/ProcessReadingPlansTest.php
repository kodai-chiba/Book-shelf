<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_reading_plan_is_changed_to_expired(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->subDay()->format('Y-m-d'),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => 'expired',
        ]);
    }

    public function test_future_reading_plan_is_not_changed_to_expired(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_reminder_is_sent_three_days_before_due_date(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        Notification::assertSentTo(
            $user,
            ReadingPlanReminder::class
        );
    }

    public function test_reminder_is_sent_on_due_date(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->format('Y-m-d'),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        Notification::assertSentTo(
            $user,
            ReadingPlanReminder::class
        );
    }

    public function test_reminder_is_sent_three_days_after_due_date(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->subDays(3)->format('Y-m-d'),
            'status' => 'expired',
            'completed_at' => null,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        Notification::assertSentTo(
            $user,
            ReadingPlanReminder::class
        );
    }

    public function test_reminder_is_not_sent_outside_notification_dates(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $this->artisan('reading-plans:process')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }
}