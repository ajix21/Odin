<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GetContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneLookupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_lookup_page_loads_for_operator(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $this->actingAs($user)->get('/phone-lookup')->assertOk();
    }

    public function test_viewer_cannot_access_phone_lookup(): void
    {
        $user = User::factory()->viewer()->create();
        $this->actingAs($user)->get('/phone-lookup')->assertForbidden();
    }

    public function test_successful_search_logs_to_db(): void
    {
        $user = User::factory()->create(['role' => 'operator']);

        $mockResult = ['success' => true, 'phone' => '+628123456789', 'profile' => ['name' => 'Test']];
        $this->mock(GetContactService::class, function ($mock) use ($mockResult) {
            $mock->shouldReceive('lookup')->once()->andReturn($mockResult);
        });

        $this->actingAs($user)->post('/phone-lookup', ['phone' => '08123456789']);

        $this->assertDatabaseHas('search_logs', [
            'user_id' => $user->id,
            'tool'    => 'getcontact',
            'query'   => '08123456789',
            'status'  => 'success',
        ]);
    }
}
