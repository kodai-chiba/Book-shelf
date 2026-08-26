<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_reading_plans_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertViewIs('reading-plans.index');
        $response->assertViewHas('readingPlans');
    }

    public function test_guest_cannot_access_reading_plans_index(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_create_reading_plan(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => now()->addDays(7)->format('Y-m-d'),
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'in_progress',
        ]);
    }

    public function test_reading_plan_cannot_be_created_without_target_date(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => '',
            ]);

        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_reading_plan_cannot_be_created_with_past_target_date(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => now()->subDay()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_reading_plan_cannot_be_created_with_nonexistent_book(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => 99999,
                'target_date' => now()->addDays(7)->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('book_id');

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => 99999,
        ]);
    }

    public function test_user_can_update_own_reading_plan(): void
    {
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

        $newDate = now()->addDays(10)->format('Y-m-d');

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => $newDate,
        ]);
    }

    public function test_user_cannot_update_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $originalDate = $readingPlan->due_date->format('Y-m-d');

        $response = $this->actingAs($otherUser)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => now()->addDays(10)->format('Y-m-d'),
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $owner->id,
            'due_date' => $originalDate,
        ]);
    }

    public function test_user_can_delete_own_reading_plan(): void
    {
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

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_user_can_complete_own_reading_plan(): void
    {
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

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $this->assertNotNull(
            $readingPlan->fresh()->completed_at
        );
    }

    public function test_user_cannot_complete_another_users_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $response = $this->actingAs($otherUser)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'user_id' => $owner->id,
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    public function test_reading_plans_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();

        $book1 = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '進行中の本',
        ]);

        $book2 = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '完了した本',
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => 'in_progress',
            ]));

        $response->assertOk();
        $response->assertSee('進行中の本');
        $response->assertDontSee('完了した本');
    }

    public function test_reading_plans_returns_no_results_with_invalid_status(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
            'title' => 'テスト書籍',
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'due_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'in_progress',
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => 'invalid_status',
            ]));

        $response->assertOk();
        $response->assertDontSee('テスト書籍');
    }
}