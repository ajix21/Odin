<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admin_cannot_access_users_page(): void
    {
        $op = User::factory()->create(['role' => 'operator']);
        $this->actingAs($op)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_view_users(): void
    {
        $this->actingAs($this->admin())->get('/admin/users')->assertOk();
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin())->post('/admin/users', [
            'name'                  => 'Test User',
            'username'              => 'testop',
            'email'                 => 'testop@example.com',
            'password'              => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role'                  => 'operator',
        ])->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', ['username' => 'testop']);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertSessionHasErrors();
    }
}
