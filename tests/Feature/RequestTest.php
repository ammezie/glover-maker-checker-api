<?php

namespace Tests\Feature;

use App\Mail\RequestCreated;
use App\Models\Request;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_admin_can_view_all_pending_requests()
    {
        Request::factory()->count(3)->create();
        Request::factory()->count(2)->approved()->create();

        Sanctum::actingAs(
            User::factory()->isAdmin()->create()
        );

        $this->getJson('/api/requests')->assertOk()
            ->assertJson([
                'status' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function an_admin_can_view_all_pending_requests_requested_by_other_admins()
    {
        $user = User::factory()->isAdmin()->create();
        Request::factory()->count(3)->create([
            'requested_by' => $user->id,
        ]);
        Request::factory()->count(2)->approved()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/requests')->assertOk()
            ->assertJson([
                'status' => true,
            ])
            ->assertJsonCount(0, 'data');
    }

    /** @test */
    public function an_admin_can_create_a_new_create_request()
    {
        $user = User::factory()->isAdmin()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/requests/', [
            'type' => 'create',
            'data' => [
                'first_name' => 'Jon',
                'last_name' => 'Snow',
                'email' => 'jonsnow@example.com',
                'password' => 'password',
            ]
        ])
            ->assertStatus(201)
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('requests', [
            'type' => 'create',
            'status' => 'pending',
            'requested_by' => $user->id,
            'data' => json_encode([
                'first_name' => 'Jon',
                'last_name' => 'Snow',
                'email' => 'jonsnow@example.com',
                'password' => 'password',
            ]),
        ]);
    }

    /** @test */
    public function an_admin_can_create_a_new_update_request()
    {
        $user = User::factory()->isAdmin()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/requests/', [
            'type' => 'update',
            'data' => [
                'user_id' => $user->id,
                'first_name' => 'Jon',
                'last_name' => 'Snow',
                'email' => 'jonsnow@example.com',
            ]
        ])
            ->assertStatus(201)
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('requests', [
            'type' => 'update',
            'status' => 'pending',
            'requested_by' => $user->id,
            'data' => json_encode([
                'user_id' => $user->id,
                'first_name' => 'Jon',
                'last_name' => 'Snow',
                'email' => 'jonsnow@example.com',
            ]),
        ]);
    }

    /** @test */
    public function an_admin_can_create_a_new_delete_request()
    {
        $user = User::factory()->isAdmin()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/requests/', [
            'type' => 'delete',
            'data' => [
                'user_id' => $user->id,
            ]
        ])
            ->assertStatus(201)
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('requests', [
            'type' => 'delete',
            'status' => 'pending',
            'requested_by' => $user->id,
            'data' => json_encode([
                'user_id' => $user->id,
            ]),
        ]);
    }

    /** @test */
    public function email_notification_is_sent_to_other_admins_when_a_new_request_is_created()
    {
        Mail::fake();

        Sanctum::actingAs(User::factory()->isAdmin()->create());

        // create 3 other admins
        User::factory()->count(3)->isAdmin()->create();

        $this->postJson('/api/requests/', [
            'type' => 'delete',
            'data' => [
                'user_id' => 1,
            ]
        ]);

        Mail::assertSent(RequestCreated::class, 3);
    }

    /** @test */
    public function an_admin_can_approve_a_pending_request_of_type_create()
    {
        Sanctum::actingAs(User::factory()->isAdmin()->create());

        $request = Request::factory()->create([
            'data' => [
                'first_name' => 'Bel',
                'last_name' => 'Air',
                'email' => 'test@example.com',
                'password' => 'password'
            ]
        ]);

        $this->postJson('/api/requests/' . $request->id . '/approve')
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Bel',
            'last_name' => 'Air',
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'approved'
        ]);
    }

    /** @test */
    public function an_admin_can_approve_a_pending_request_of_type_update()
    {
        $user = User::factory()->create([
            'first_name' => 'Bel',
            'last_name' => 'Air',
            'email' => 'test@example.com',
        ]);

        Sanctum::actingAs(User::factory()->isAdmin()->create());

        $request = Request::factory()->create([
            'type' =>  'update',
            'data' => [
                'user_id' => $user->id,
                'first_name' => 'Belli',
                'last_name' => 'Aired',
                'email' => 'tested@example.com',
            ],
        ]);

        $this->postJson('/api/requests/' . $request->id . '/approve')
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Belli',
            'last_name' => 'Aired',
            'email' => 'tested@example.com',
        ]);

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'approved'
        ]);
    }

    /** @test */
    public function an_admin_can_approve_a_pending_request_of_type_delete()
    {
        $user = User::factory()->create();

        Sanctum::actingAs(User::factory()->isAdmin()->create());

        $request = Request::factory()->create([
            'type' =>  'delete',
            'data' => [
                'user_id' => $user->id,
            ],
        ]);

        $this->postJson('/api/requests/' . $request->id . '/approve')
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDeleted($user);

        $this->assertDatabaseHas('requests', [
            'id' => $request->id,
            'status' => 'approved'
        ]);
    }

    /** @test */
    public function an_admin_cannot_approve_own_request()
    {
        $user = User::factory()->isAdmin()->create();
        Sanctum::actingAs($user);

        $request = Request::factory()->create([
            'requested_by' => $user->id,
        ]);

        $this->postJson('/api/requests/' . $request->id . '/approve')
            ->assertStatus(403)
            ->assertJson([
                'status' => false,
            ]);
    }

    /** @test */
    public function an_admin_cannot_approve_an_already_approved_request()
    {
        Sanctum::actingAs(
            User::factory()->isAdmin()->create()
        );

        $request = Request::factory()->approved()->create();

        $this->postJson('/api/requests/' . $request->id . '/approve')
            ->assertStatus(403)
            ->assertJson([
                'status' => false,
            ]);
    }

    /** @test */
    public function an_admin_can_decline_a_pending_request()
    {
        Sanctum::actingAs(
            User::factory()->isAdmin()->create()
        );

        $request = Request::factory()->create();

        $this->postJson('/api/requests/' . $request->id . '/decline')
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDeleted($request);
    }

    /** @test */
    public function an_admin_cannot_decline_an_already_approved_request()
    {
        Sanctum::actingAs(
            User::factory()->isAdmin()->create()
        );

        $request = Request::factory()->approved()->create();

        $this->postJson('/api/requests/' . $request->id . '/decline')
            ->assertStatus(403)
            ->assertJson([
                'status' => false,
            ]);

        $this->assertModelExists($request);
    }
}
