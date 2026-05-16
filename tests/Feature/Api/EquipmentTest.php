<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Equipment;
use App\Models\User;
use App\Enums\EquipmentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_authenticated_can_view_equipment_list(): void
    {
        Equipment::factory()->count(2)->create();
        $user = User::factory()->member()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/equipment', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'links',
                'meta' => [
                    'summary' => [
                        'total_available',
                        'total_borrowed',
                        'total_maintenance',
                    ],
                ],
            ])
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_equipment(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $payload = [
            'name' => 'Kamera Test D5200',
            'category' => 'kamera',
            'description' => 'Body DSLR untuk uji API.',
            'stock' => 2,
        ];

        $response = $this->postJson('/api/v1/equipment', $payload, [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'category',
                    'description',
                    'status' => [
                        'value',
                        'label',
                        'color',
                    ],
                    'stock',
                    'is_available',
                    'image_url',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Kamera Test D5200');

        $this->assertDatabaseHas('equipment', [
            'name' => 'Kamera Test D5200',
        ]);
    }

    public function test_member_cannot_create_equipment(): void
    {
        $member = User::factory()->member()->create();
        $token = $member->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/equipment', [
            'name' => 'Harusnya Gagal',
            'category' => 'lainnya',
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertForbidden()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Aksi ini terbatas untuk administrator.');
    }

    public function test_admin_can_update_equipment(): void
    {
        $equipment = Equipment::factory()->create([
            'name' => 'Item Lama',
            'category' => 'kamera',
        ]);
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->putJson(
            '/api/v1/equipment/'.$equipment->id,
            [
                'name' => 'Item Diperbarui',
                'category' => 'kamera',
                'description' => 'Deskripsi baru.',
            ],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Item Diperbarui');

        $this->assertDatabaseHas('equipment', [
            'id' => $equipment->id,
            'name' => 'Item Diperbarui',
        ]);
    }

    public function test_admin_can_delete_equipment_without_active_booking(): void
    {
        $equipment = Equipment::factory()->create([
            'status' => EquipmentStatus::Available,
            'stock' => 2,
        ]);
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->deleteJson(
            '/api/v1/equipment/'.$equipment->id,
            [],
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', null);

        $this->assertSoftDeleted('equipment', [
            'id' => $equipment->id,
        ]);
    }
}
