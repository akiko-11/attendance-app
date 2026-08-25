<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'admin_status' => false,
        ]);

        User::factory()->create([
            'name' => 'ユーザー2',
            'email' => 'user2@example.com',
            'admin_status' => false,
        ]);

        User::factory()->create([
            'name' => 'ユーザー3',
            'email' => 'user3@example.com',
            'admin_status' => true,
        ]);
    }
}
