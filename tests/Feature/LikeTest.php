<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_like_review(): void
    {
        $reviewOwner = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $reviewOwner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
        ]);

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('likes', [
            'review_id' => $review->id,
        ]);
    }

    public function test_authenticated_user_can_like_review(): void
    {
        $reviewOwner = User::factory()->create();
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $reviewOwner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_authenticated_user_can_unlike_review(): void
    {
        $reviewOwner = User::factory()->create();
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $reviewOwner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
        ]);

        $user->likedReviews()->attach($review->id);

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_user_can_like_multiple_reviews(): void
    {
        $bookOwner = User::factory()->create();
        $reviewOwner1 = User::factory()->create();
        $reviewOwner2 = User::factory()->create();
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $bookOwner->id,
        ]);

        $review1 = Review::factory()->create([
            'user_id' => $reviewOwner1->id,
            'book_id' => $book->id,
        ]);

        $review2 = Review::factory()->create([
            'user_id' => $reviewOwner2->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user)
            ->post(route('reviews.like', $review1));

        $this->actingAs($user)
            ->post(route('reviews.like', $review2));

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'review_id' => $review1->id,
        ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'review_id' => $review2->id,
        ]);
    }

    public function test_multiple_users_can_like_same_review(): void
    {
        $reviewOwner = User::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $reviewOwner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user1)
            ->post(route('reviews.like', $review));

        $this->actingAs($user2)
            ->post(route('reviews.like', $review));

        $this->assertDatabaseHas('likes', [
            'user_id' => $user1->id,
            'review_id' => $review->id,
        ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user2->id,
            'review_id' => $review->id,
        ]);
    }
}