<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_regsiter_as_an_admin()
    {
        $this->postJson('/api/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password',
            'is_admin' => true
        ])->assertStatus(201)
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'johndoe@example.com',
            'is_admin' => true
        ]);
    }

    /** @test */
    public function an_admin_can_log_in()
    {
        $user = User::factory()->isAdmin()->create([
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password'),
        ]);

        $res = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJson([
                'status' => true,
            ]);
    }

    /** @test */
    public function an_admin_cannot_log_in_invalid_credentials()
    {
        $user = User::factory()->isAdmin()->create([
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password'),
        ]);

        $res = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertStatus(401)
            ->assertJson([
                'status' => false,
            ]);
    }

    /** @test */
    public function a_non_admin_cannot_log_in()
    {
        $user = User::factory()->create([
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password'),
        ]);

        $res = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertStatus(401)
            ->assertJson([
                'status' => false,
            ]);
    }
}
