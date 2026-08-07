<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_belongs_to_creator(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $book->creator);
        $this->assertEquals($user->id, $book->creator->id);
    }

    public function test_book_has_many_reviews(): void
    {
        $owner = User::factory()->create();
        $reviewer = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
        ]);

        $this->assertCount(1, $book->reviews);
        $this->assertInstanceOf(Review::class, $book->reviews->first());
    }

    public function test_book_belongs_to_many_genres(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $genre = Genre::factory()->create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        $this->assertCount(1, $book->genres);
        $this->assertInstanceOf(Genre::class, $book->genres->first());
    }

    public function test_book_can_be_favorited_by_users(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $user->favoriteBooks()->attach($book->id);

        $this->assertCount(1, $book->favorites);
        $this->assertInstanceOf(Favorite::class, $book->favorites->first());
    }

    public function test_book_fillable_attributes(): void
    {
        $book = new Book();

        $this->assertEquals([
            'title',
            'author',
            'isbn',
            'published_date',
            'description',
            'image_url',
            'created_by',
        ], $book->getFillable());
    }
}