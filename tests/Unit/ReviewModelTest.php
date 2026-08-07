<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Like;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($user->id, $review->user->id);
    }

    public function test_review_belongs_to_book(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $this->assertInstanceOf(Book::class, $review->book);
        $this->assertEquals($book->id, $review->book->id);
    }

    public function test_review_has_many_likes(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $likeUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $likeUser->likedReviews()->attach($review->id);

        $this->assertCount(1, $review->likes);
        $this->assertInstanceOf(Like::class, $review->likes->first());
    }

    public function test_review_can_be_liked_by_users(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $user->likedReviews()->attach($review->id);

        $this->assertCount(1, $review->likedByUsers);
        $this->assertInstanceOf(User::class, $review->likedByUsers->first());
    }

    public function test_review_fillable_attributes(): void
    {
        $review = new Review();

        $this->assertEquals([
            'user_id',
            'book_id',
            'rating',
            'comment',
        ], $review->getFillable());
    }
}