<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_ranking_page(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertViewIs('ranking.index');
        $response->assertViewHas('rankedBooks');
    }

    public function test_books_are_ranked_by_average_rating_descending(): void
    {
        $user = User::factory()->create();

        $highRatedBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '高評価の本',
        ]);

        $middleRatedBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '中評価の本',
        ]);

        $lowRatedBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '低評価の本',
        ]);

        Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $middleRatedBook->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 1,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertViewHas('rankedBooks', function ($rankedBooks) use (
            $highRatedBook,
            $middleRatedBook,
            $lowRatedBook
        ) {
            return $rankedBooks->pluck('id')->values()->all() === [
                $highRatedBook->id,
                $middleRatedBook->id,
                $lowRatedBook->id,
            ];
        });
    }

    public function test_book_without_reviews_is_not_displayed_in_ranking(): void
    {
        $user = User::factory()->create();

        $reviewedBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => 'レビューあり',
        ]);

        $unreviewedBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => 'レビューなし',
        ]);

        Review::factory()->create([
            'user_id' => User::factory()->create()->id,
            'book_id' => $reviewedBook->id,
            'rating' => 5,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSee('レビューあり');
        $response->assertDontSee('レビューなし');

        $response->assertViewHas('rankedBooks', function ($rankedBooks) use (
            $reviewedBook,
            $unreviewedBook
        ) {
            return $rankedBooks->contains('id', $reviewedBook->id)
                && !$rankedBooks->contains('id', $unreviewedBook->id);
        });
    }

    public function test_ranking_displays_only_top_ten_books(): void
    {
        $bookOwner = User::factory()->create();

        for ($i = 1; $i <= 12; $i++) {
            $book = Book::factory()->create([
                'created_by' => $bookOwner->id,
                'title' => "ランキング本{$i}",
            ]);

            Review::factory()->create([
                'user_id' => User::factory()->create()->id,
                'book_id' => $book->id,
                'rating' => ($i % 5) + 1,
            ]);
        }

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertViewHas('rankedBooks', function ($rankedBooks) {
            return $rankedBooks->count() === 10;
        });
    }
}