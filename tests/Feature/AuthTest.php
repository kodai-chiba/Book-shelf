<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_guest_can_register(): void
    {
        $response = $this->post(route('register'), [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
        ]);
    }

    public function test_user_cannot_register_without_name(): void
    {
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'yamada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('name');

        $this->assertGuest();

        $this->assertDatabaseMissing('users', [
            'email' => 'yamada@example.com',
        ]);
    }

    public function test_user_cannot_register_with_invalid_email(): void
    {
        $response = $this->post(route('register'), [
            'name' => '山田太郎',
            'email' => 'invalid-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'yamada@example.com',
        ]);

        $response = $this->post(route('register'), [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_user_cannot_register_without_password_confirmation(): void
    {
        $response = $this->post(route('register'), [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => 'password',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_registered_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'yamada@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'yamada@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'yamada@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'yamada@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('logout'));

        $this->assertGuest();
    }
}