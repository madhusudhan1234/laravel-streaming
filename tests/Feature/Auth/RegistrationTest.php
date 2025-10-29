<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        // Create and authenticate a user since registration requires auth
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        // Create and authenticate a user since registration requires auth
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Check that the new user was created
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
