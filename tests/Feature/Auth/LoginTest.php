<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_page_for_protected_routes(): void
    {
        $this->get('/courses')
            ->assertRedirect('/login');
    }

    public function test_user_can_login_and_logout(): void
    {
        $password = 'secret-password';
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => $password,
        ]);

        $this->post('/login', [
            'email' => 'owner@example.com',
            'password' => $password,
        ])->assertRedirect('/courses');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_too_many_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'correct-password',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => 'owner@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->from('/login')->post('/login', [
            'email' => 'owner@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
