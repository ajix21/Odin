<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'username'  => 'testuser',
            'role'      => 'operator',
            'is_active' => true,
        ], $attrs));
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }

    public function test_authenticated_user_redirected_from_login(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
    }

    public function test_login_with_valid_credentials(): void
    {
        $this->makeUser(['password' => bcrypt('password123')]);
        $this->post('/login', ['username' => 'testuser', 'password' => 'password123'])
             ->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_with_invalid_password(): void
    {
        $this->makeUser();
        $this->post('/login', ['username' => 'testuser', 'password' => 'wrongpass'])
             ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->makeUser(['is_active' => false, 'password' => bcrypt('password123')]);
        $this->post('/login', ['username' => 'testuser', 'password' => 'password123'])
             ->assertSessionHasErrors('username');
    }

    public function test_logout(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
