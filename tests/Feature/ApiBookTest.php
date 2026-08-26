<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_can_get_paginated_book_list(): void
    {
        $user = User::factory()->create();

        Book::factory()->count(12)->create([
            'created_by' => $user->id,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'author',
                        'isbn',
                        'published_date',
                        'description',
                        'image_url',
                        'genres',
                        'average_rating',
                        'reviews_count',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_api_can_search_books_by_keyword(): void
    {
        $user = User::factory()->create();

        Book::factory()->create([
            'created_by' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
        ]);

        Book::factory()->create([
            'created_by' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $response = $this->getJson(
            '/api/v1/books?keyword=' . urlencode('猫')
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '吾輩は猫である');
    }

    public function test_api_can_sort_books_by_average_rating_descending(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer1 = User::factory()->create();
        $reviewer2 = User::factory()->create();

        $highRatedBook = Book::factory()->create([
            'created_by' => $bookOwner->id,
            'title' => '高評価の本',
        ]);

        $lowRatedBook = Book::factory()->create([
            'created_by' => $bookOwner->id,
            'title' => '低評価の本',
        ]);

        Review::factory()->create([
            'user_id' => $reviewer1->id,
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $reviewer2->id,
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        $response = $this->getJson(
            '/api/v1/books?sort=rating_desc'
        );

        $response->assertOk()
            ->assertJsonPath('data.0.id', $highRatedBook->id)
            ->assertJsonPath('data.1.id', $lowRatedBook->id);
    }

    public function test_api_can_get_book_detail(): void
    {
        $bookOwner = User::factory()->create();
        $reviewer = User::factory()->create();

        $genre = Genre::create([
            'name' => 'API詳細テストジャンル',
        ]);

        $book = Book::factory()->create([
            'created_by' => $bookOwner->id,
            'title' => 'API詳細テスト書籍',
        ]);

        $book->genres()->attach($genre->id);

        $review = Review::factory()->create([
            'user_id' => $reviewer->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '詳細APIのテストコメント',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'API詳細テスト書籍')
            ->assertJsonPath('data.genres.0.id', $genre->id)
            ->assertJsonPath('data.reviews.0.id', $review->id)
            ->assertJsonPath('data.reviews.0.rating', 5)
            ->assertJsonPath(
                'data.reviews.0.comment',
                '詳細APIのテストコメント'
            )
            ->assertJsonPath(
                'data.reviews.0.user.name',
                $reviewer->name
            );
    }

    public function test_api_returns_404_for_nonexistent_book(): void
    {
        $response = $this->getJson('/api/v1/books/99999');

        $response->assertNotFound();
    }

    public function test_api_can_create_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genre = Genre::create([
            'name' => 'API登録テストジャンル',
        ]);

        $data = $this->validBookData($genre);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'APIテスト書籍')
            ->assertJsonPath('data.author', '山田太郎')
            ->assertJsonPath('data.genres.0.id', $genre->id);

        $this->assertDatabaseHas('books', [
            'title' => 'APIテスト書籍',
            'isbn' => '9781234567890',
            'created_by' => $user->id,
        ]);

        $book = Book::where('isbn', '9781234567890')
            ->firstOrFail();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_api_cannot_create_book_without_title(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genre = Genre::create([
            'name' => 'API必須項目テストジャンル',
        ]);

        $data = $this->validBookData($genre, [
            'title' => '',
        ]);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('title');

        $this->assertDatabaseMissing('books', [
            'isbn' => '9781234567890',
        ]);
    }

    public function test_api_cannot_create_book_with_duplicate_isbn(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genre = Genre::create([
            'name' => 'API重複ISBNテストジャンル',
        ]);

        Book::factory()->create([
            'created_by' => $user->id,
            'isbn' => '9781234567890',
        ]);

        $data = $this->validBookData($genre, [
            'title' => 'ISBN重複テスト書籍',
            'isbn' => '9781234567890',
        ]);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }

    public function test_api_can_update_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $oldGenre = Genre::create([
            'name' => 'API更新前ジャンル',
        ]);

        $newGenre = Genre::create([
            'name' => 'API更新後ジャンル',
        ]);

        $book = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '更新前タイトル',
            'isbn' => '9781234567890',
        ]);

        $book->genres()->attach($oldGenre->id);

        $data = $this->validBookData($newGenre, [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9781234567890',
            'description' => 'APIで更新しました。',
        ]);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', '更新後タイトル')
            ->assertJsonPath('data.genres.0.id', $newGenre->id);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);
    }

    public function test_api_update_ignores_current_books_isbn(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genre = Genre::create([
            'name' => 'API ISBN更新テストジャンル',
        ]);

        $book = Book::factory()->create([
            'created_by' => $user->id,
            'isbn' => '9781234567890',
        ]);

        $data = $this->validBookData($genre, [
            'title' => 'ISBNを変えずに更新',
            'isbn' => '9781234567890',
        ]);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertOk()
            ->assertJsonPath(
                'data.title',
                'ISBNを変えずに更新'
            );
    }

    public function test_api_can_delete_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertOk();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->getJson("/api/v1/books/{$book->id}")
            ->assertNotFound();
    }

    private function validBookData(
        Genre $genre,
        array $overrides = []
    ): array {
        return array_merge([
            'title' => 'APIテスト書籍',
            'author' => '山田太郎',
            'isbn' => '9781234567890',
            'published_date' => '2026-08-06',
            'description' => '公開APIのテスト用書籍です。',
            'image_url' => 'https://example.com/api-book.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }

    public function test_unauthenticated_user_cannot_create_book_via_api(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'Sanctum認証テスト',
        ]);

        $data = $this->validBookData($genre);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertUnauthorized();

        $this->assertDatabaseMissing('books', [
            'isbn' => '9781234567890',
        ]);
    }

    public function test_authenticated_user_can_create_book_via_api(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'Sanctum正常系テスト',
        ]);

        Sanctum::actingAs($user);

        $data = $this->validBookData($genre, [
            'title' => 'Sanctumテスト書籍',
            'isbn' => '9781234567890',
        ]);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertCreated();

        $this->assertDatabaseHas('books', [
            'title' => 'Sanctumテスト書籍',
            'isbn' => '9781234567890',
            'created_by' => $user->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_update_book_via_api(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '未認証更新テスト',
        ]);

        $book = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '更新前タイトル',
        ]);

        $data = $this->validBookData($genre, [
            'title' => '更新後タイトル',
            'isbn' => $book->isbn,
        ]);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertUnauthorized();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);
    }

    public function test_authenticated_user_can_update_own_book_via_api(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'Sanctum更新正常系テスト',
        ]);

        $book = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '更新前タイトル',
        ]);

        Sanctum::actingAs($user);

        $data = $this->validBookData($genre, [
            'title' => '更新後タイトル',
            'isbn' => $book->isbn,
        ]);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);
    }

    public function test_authenticated_user_cannot_update_another_users_book_via_api(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'API認可テスト',
        ]);

        $book = Book::factory()->create([
            'created_by' => $owner->id,
            'title' => '変更前タイトル',
        ]);

        Sanctum::actingAs($otherUser);

        $data = $this->validBookData($genre, [
            'title' => '不正な変更後タイトル',
            'isbn' => $book->isbn,
        ]);

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $data
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '変更前タイトル',
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_book_via_api(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertUnauthorized();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_authenticated_user_can_delete_own_book_via_api(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertOk();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_authenticated_user_cannot_delete_another_users_book_via_api(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}