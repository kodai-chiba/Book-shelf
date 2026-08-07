<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_guest_is_redirected_to_genres_from_root(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('genres.index'));
    }

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    public function test_guest_can_view_register_page(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_guest_cannot_access_books_index(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_genres_index(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertRedirect(route('login'));
    }
}