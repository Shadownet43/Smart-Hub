<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $payload = [
            'name' => 'Pengguna Uji',
            'email' => 'uji@smarthub.test',
            'password' => 'password-sandi',
            'password_confirmation' => 'password-sandi',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'phone',
                        'created_at',
                    ],
                    'token',
                    'token_type',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'uji@smarthub.test');

        $this->assertDatabaseHas('users', [
            'email' => 'uji@smarthub.test',
        ]);
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'ada@smarthub.test',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Orang Lain',
            'email' => 'ada@smarthub.test',
            'password' => 'password-sandi',
            'password_confirmation' => 'password-sandi',
        ]);

        $response->assertUnprocessable()
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@smarthub.test',
            'password' => 'secret1234',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@smarthub.test',
            'password' => 'secret1234',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                    'token_type',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'login@smarthub.test')
            ->assertJsonPath('data.token_type', 'Bearer');
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'pasti@smarthub.test',
            'password' => 'benar-sandi',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pasti@smarthub.test',
            'password' => 'salah-sandi',
        ]);

        $response->assertUnauthorized()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                    'token_type',
                ],
            ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.token', null);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $plainToken = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer '.$plainToken,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                    'token_type',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user', null);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }
}
