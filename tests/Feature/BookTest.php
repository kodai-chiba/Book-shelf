<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_books_index(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_books_index(): void
    {
        $user = User::factory()->create();

        Book::factory()->count(3)->create([
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('books.index'));

        $response->assertOk();
        $response->assertViewIs('books.index');
        $response->assertViewHas('books');
    }

    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '登録テストジャンル',
        ]);

        $data = $this->validBookData($genre);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567890',
            'created_by' => $user->id,
        ]);

        $book = Book::where('isbn', '9781234567890')->firstOrFail();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_book_owner_can_update_book(): void
    {
        $user = User::factory()->create();

        $oldGenre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);

        $newGenre = Genre::factory()->create([
            'name' => '更新後ジャンル',
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
            'description' => '更新後の説明',
        ]);

        $response = $this->actingAs($user)
            ->put(route('books.update', $book), $data);

        $response->assertRedirect(route('books.index'));

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

    public function test_book_owner_can_delete_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_book_cannot_be_created_without_title(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '必須項目テストジャンル',
        ]);

        $data = $this->validBookData($genre, [
            'title' => '',
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors('title');

        $this->assertDatabaseMissing('books', [
            'isbn' => '9781234567890',
        ]);
    }

    public function test_book_cannot_be_created_with_duplicate_isbn(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ISBNテストジャンル',
        ]);

        Book::factory()->create([
            'created_by' => $user->id,
            'isbn' => '9781234567890',
        ]);

        $data = $this->validBookData($genre, [
            'title' => 'ISBN重複書籍',
            'isbn' => '9781234567890',
        ]);

        $response = $this->actingAs($user)
            ->post(route('books.store'), $data);

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }

    public function test_user_cannot_edit_another_users_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('books.edit', $book));

        $response->assertForbidden();
    }

    public function test_user_cannot_update_another_users_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '不正更新テストジャンル',
        ]);

        $book = Book::factory()->create([
            'created_by' => $owner->id,
            'title' => '変更前タイトル',
        ]);

        $data = $this->validBookData($genre, [
            'title' => '不正な変更後タイトル',
            'isbn' => $book->isbn,
        ]);

        $response = $this->actingAs($otherUser)
            ->put(route('books.update', $book), $data);

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '変更前タイトル',
        ]);
    }

    public function test_user_cannot_delete_another_users_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_books_can_be_searched_by_keyword(): void
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

        $response = $this->actingAs($user)
            ->get(route('books.index', ['keyword' => '猫']));

        $response->assertOk();
        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('Laravel入門');
    }

    public function test_books_search_returns_no_results_when_keyword_does_not_match(): void
    {
        $user = User::factory()->create();

        Book::factory()->create([
            'created_by' => $user->id,
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
        ]);

        $response = $this->actingAs($user)
            ->get(route('books.index', ['keyword' => '存在しないキーワード']));

        $response->assertOk();
        $response->assertSee('書籍が見つかりませんでした。');
        $response->assertDontSee('吾輩は猫である');
    }

    public function test_books_can_be_searched_by_author(): void
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

        $response = $this->actingAs($user)
            ->get(route('books.index', ['keyword' => '夏目漱石']));

        $response->assertOk();
        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('Laravel入門');
    }

    public function test_books_can_be_filtered_by_genre(): void
    {
        $user = User::factory()->create();

        $novel = Genre::factory()->create([
            'name' => '小説',
        ]);

        $technical = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $novelBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '吾輩は猫である',
        ]);

        $technicalBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => 'Laravel入門',
        ]);

        $novelBook->genres()->attach($novel->id);
        $technicalBook->genres()->attach($technical->id);

        $response = $this->actingAs($user)
            ->get(route('books.index', ['genre' => $novel->id]));

        $response->assertOk();
        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('Laravel入門');
    }

    public function test_books_filter_returns_no_results_when_genre_has_no_books(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '未使用ジャンル',
        ]);

        Book::factory()->create([
            'created_by' => $user->id,
            'title' => '吾輩は猫である',
        ]);

        $response = $this->actingAs($user)
            ->get(route('books.index', ['genre' => $genre->id]));

        $response->assertOk();
        $response->assertSee('書籍が見つかりませんでした。');
        $response->assertDontSee('吾輩は猫である');
    }

    public function test_books_can_be_sorted_by_newest(): void
    {
        $user = User::factory()->create();

        $oldBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        $newBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('books.index', ['sort' => 'newest']));

        $response->assertOk();
        $response->assertSeeInOrder([
            $newBook->title,
            $oldBook->title,
        ]);
    }

    public function test_books_can_be_sorted_by_oldest(): void
    {
        $user = User::factory()->create();

        $oldBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        $newBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('books.index', ['sort' => 'oldest']));

        $response->assertOk();
        $response->assertSeeInOrder([
            $oldBook->title,
            $newBook->title,
        ]);
    }

    public function test_books_can_be_sorted_by_rating_with_unreviewed_books_last(): void
    {
        $owner = User::factory()->create();
        $reviewer1 = User::factory()->create();
        $reviewer2 = User::factory()->create();

        $highRatedBook = Book::factory()->create([
            'created_by' => $owner->id,
            'title' => '高評価の本',
        ]);

        $lowRatedBook = Book::factory()->create([
            'created_by' => $owner->id,
            'title' => '低評価の本',
        ]);

        $unreviewedBook = Book::factory()->create([
            'created_by' => $owner->id,
            'title' => '未評価の本',
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

        $response = $this->actingAs($owner)
            ->get(route('books.index', ['sort' => 'rating']));

        $response->assertOk();
        $response->assertSeeInOrder([
            $highRatedBook->title,
            $lowRatedBook->title,
            $unreviewedBook->title,
        ]);
    }

    public function test_book_information_can_be_searched_by_isbn(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '吾輩は猫である',
                            'authors' => ['夏目漱石'],
                            'publishedDate' => '1905-01-01',
                            'description' => 'ISBN検索テスト',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/cat.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn.search', [
                'isbn' => '9781234567890',
            ]));

        $response->assertOk()
            ->assertJson([
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'published_date' => '1905-01-01',
                'description' => 'ISBN検索テスト',
                'image_url' => 'https://example.com/cat.jpg',
                'isbn' => '9781234567890',
            ]);
    }

    public function test_isbn_search_returns_422_when_isbn_is_invalid(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn.search', [
                'isbn' => '123456789012',
            ]));

        $response->assertUnprocessable()
            ->assertJson([
                'error' => 'ISBNは13桁の数字で入力してください。',
            ]);
    }

    public function test_isbn_search_returns_404_when_book_is_not_found(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn.search', [
                'isbn' => '9781234567890',
            ]));

        $response->assertNotFound()
            ->assertJson([
                'error' => '該当する書籍が見つかりませんでした。',
            ]);
    }

    public function test_isbn_search_returns_500_when_google_books_api_fails(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([], 500),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('books.isbn.search', [
                'isbn' => '9781234567890',
            ]));

        $response->assertInternalServerError()
            ->assertJson([
                'error' => '書籍情報の取得に失敗しました。',
            ]);
    }

    private function validBookData(
        Genre $genre,
        array $overrides = []
    ): array {
        return array_merge([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'テスト用の書籍です。',
            'image_url' => 'https://example.com/test.jpg',
            'genres' => [$genre->id],
        ], $overrides);
    }
}