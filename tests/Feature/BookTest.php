<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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