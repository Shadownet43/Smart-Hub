<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@smarthub.com'],
            [
                'name' => 'Admin Smart-Hub',
                'password' => 'password',
                'role' => UserRole::Admin,
                'phone' => null,
            ],
        );

        foreach (range(1, 5) as $i) {
            User::query()->updateOrCreate(
                ['email' => "member{$i}@smarthub.com"],
                [
                    'name' => "Anggota {$i}",
                    'password' => 'password',
                    'role' => UserRole::Member,
                    'phone' => null,
                ],
            );
        }
    }
}
