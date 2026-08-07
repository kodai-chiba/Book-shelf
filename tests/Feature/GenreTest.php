<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_genres_index(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_genres_index(): void
    {
        $user = User::factory()->create();

        Genre::create([
            'name' => '小説',
        ]);

        Genre::create([
            'name' => '技術書',
        ]);

        $response = $this->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
        $response->assertSee('小説');
        $response->assertSee('技術書');
    }

    public function test_authenticated_user_can_create_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'テストジャンル',
            ]);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを作成しました。');

        $this->assertDatabaseHas('genres', [
            'name' => 'テストジャンル',
        ]);
    }

    public function test_genre_cannot_be_created_without_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('genres', [
            'name' => '',
        ]);
    }

    public function test_genre_cannot_be_created_with_duplicate_name(): void
    {
        $user = User::factory()->create();

        Genre::create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '小説',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_authenticated_user_can_view_genre_detail(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.show');
        $response->assertViewHasAll([
            'genre',
            'books',
        ]);
        $response->assertSee($book->title);
    }

    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '更新前ジャンル',
        ]);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '更新後ジャンル',
            ]);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを更新しました。');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新後ジャンル',
        ]);
    }

    public function test_genre_cannot_be_updated_to_duplicate_name(): void
    {
        $user = User::factory()->create();

        Genre::create([
            'name' => '小説',
        ]);

        $genre = Genre::create([
            'name' => '技術書',
        ]);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '小説',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '技術書',
        ]);
    }

    public function test_authenticated_user_can_delete_genre_without_books(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '削除対象ジャンル',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを削除しました。');

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $genre = Genre::create([
            'name' => '書籍ありジャンル',
        ]);

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas(
            'error',
            '書籍が紐づいているジャンルは削除できません。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }
}