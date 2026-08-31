<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;

    // 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_admin_can_view_all_general_users(): void
    {
        $admin = User::factory()->create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'admin_status' => true,
        ]);

        $user1 = User::factory()->create([
            'name' => '一般ユーザー1',
            'email' => 'user1@example.com',
            'admin_status' => false,
        ]);

        $user2 = User::factory()->create([
            'name' => '一般ユーザー2',
            'email' => 'user2@example.com',
            'admin_status' => false,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertDontSeeText($admin->name);
        $response->assertDontSeeText($admin->email);
        $response->assertSeeText($user1->name);
        $response->assertSeeText($user1->email);
        $response->assertSeeText($user2->name);
        $response->assertSeeText($user2->email);
    }
}
