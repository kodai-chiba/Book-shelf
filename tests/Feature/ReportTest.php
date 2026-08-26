<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_reading_report_summary(): void
    {
        $user = User::factory()->create();

        $book1 = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $book2 = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 3,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'due_date' => now()->addDay(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 2
                && $stats['summary']['books_read'] === 1
                && (float) $stats['summary']['average_rating'] === 4.0;
        });
    }

    public function test_report_does_not_include_other_users_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userBook = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $otherBook = Book::factory()->create([
            'created_by' => $otherUser->id,
        ]);

        // 自分のレビュー
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $userBook->id,
            'rating' => 5,
        ]);

        // 他ユーザーのレビュー
        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'rating' => 1,
        ]);

        // 自分の読了
        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $userBook->id,
            'due_date' => now()->addDay(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        // 他ユーザーの読了
        ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'due_date' => now()->addDay(),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 1
                && $stats['summary']['books_read'] === 1
                && (float) $stats['summary']['average_rating'] === 5.0;
        });
    }

    public function test_report_has_correct_rating_distribution(): void
    {
        $user = User::factory()->create();

        $ratings = [1, 2, 3, 4, 5, 5];

        foreach ($ratings as $rating) {
            $book = Book::factory()->create([
                'created_by' => $user->id,
            ]);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['rating_distribution']->toArray() === [
                1,
                1,
                1,
                1,
                2,
            ];
        });
    }

    public function test_report_has_top_five_high_rated_books(): void
    {
        $user = User::factory()->create();

        $ratings = [5, 5, 4, 4, 4, 3];

        foreach ($ratings as $index => $rating) {
            $book = Book::factory()->create([
                'created_by' => $user->id,
                'title' => 'テスト書籍' . ($index + 1),
            ]);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $topRatedBooks = $stats['top_rated_books'];

            return $topRatedBooks->count() === 5
                && $topRatedBooks->every(
                    fn ($book) => $book['rating'] >= 4
                );
        });
    }

    public function test_report_has_genre_rating_statistics(): void
    {
        $user = User::factory()->create();

        $genre1 = Genre::factory()->create([
            'name' => '小説',
        ]);

        $genre2 = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book1 = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $book2 = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $book3 = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $book1->genres()->attach($genre1->id);
        $book2->genres()->attach($genre1->id);
        $book3->genres()->attach($genre2->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book3->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) use ($genre1, $genre2) {
            $genreRatings = $stats['genre_ratings'];

            $novel = $genreRatings->firstWhere('id', $genre1->id);
            $technical = $genreRatings->firstWhere('id', $genre2->id);

            return $novel !== null
                && $novel['count'] === 2
                && (float) $novel['average_rating'] === 4.0
                && $technical !== null
                && $technical['count'] === 1
                && (float) $technical['average_rating'] === 5.0;
        });
    }

    public function test_report_handles_empty_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 0
                && $stats['summary']['books_read'] === 0
                && (float) $stats['summary']['average_rating'] === 0.0
                && $stats['rating_distribution']->toArray() === [0, 0, 0, 0, 0]
                && $stats['top_rated_books']->isEmpty()
                && $stats['genre_ratings']->isEmpty();
        });
    }
}