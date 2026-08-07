<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $data = [
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ];

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ]);
    }

    public function test_review_cannot_be_created_with_invalid_rating(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $data = [
            'rating' => 6,
            'comment' => '評価が不正です。',
        ];

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 6,
        ]);
    }

    public function test_review_cannot_be_created_without_rating(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $data = [
            'comment' => '評価を入れていません。',
        ];

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), $data);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_review_owner_can_update_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);

        $data = [
            'rating' => 5,
            'comment' => '更新後コメント',
        ];

        $response = $this->actingAs($user)
            ->put(route('reviews.update', $review), $data);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '更新後コメント',
        ]);
    }

    public function test_review_owner_can_delete_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_user_cannot_edit_another_users_review(): void
    {
        $reviewOwner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $reviewOwner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('reviews.edit', $review));

        $response->assertForbidden();
    }

    public function test_user_cannot_update_another_users_review(): void
    {
        $reviewOwner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $reviewOwner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '変更前',
        ]);

        $data = [
            'rating' => 5,
            'comment' => '不正な変更',
        ];

        $response = $this->actingAs($otherUser)
            ->put(route('reviews.update', $review), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '変更前',
        ]);
    }

    public function test_user_cannot_delete_another_users_review(): void
    {
        $reviewOwner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $reviewOwner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete(route('reviews.destroy', $review));

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }
}