<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_favorites_index(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_favorites_index(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $user->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();
        $response->assertViewIs('favorites.index');
        $response->assertViewHas('books');
        $response->assertSee($book->title);
    }

    public function test_authenticated_user_can_add_book_to_favorites(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_authenticated_user_can_remove_book_from_favorites(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $user->favoriteBooks()->attach($book->id);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('favorites.index'))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('favorites.index'));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_user_only_sees_their_own_favorite_books(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $usersFavoriteBook = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '自分のお気に入り書籍',
        ]);

        $otherUsersFavoriteBook = Book::factory()->create([
            'created_by' => $otherUser->id,
            'title' => '他人のお気に入り書籍',
        ]);

        $user->favoriteBooks()->attach($usersFavoriteBook->id);
        $otherUser->favoriteBooks()->attach($otherUsersFavoriteBook->id);

        $response = $this->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('自分のお気に入り書籍');
        $response->assertDontSee('他人のお気に入り書籍');

        $response->assertViewHas('books', function ($books) use (
            $usersFavoriteBook,
            $otherUsersFavoriteBook
        ) {
            return $books->contains('id', $usersFavoriteBook->id)
                && !$books->contains('id', $otherUsersFavoriteBook->id);
        });
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $owner = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);
    }
}