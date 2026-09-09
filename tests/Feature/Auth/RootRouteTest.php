<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RootRouteTest extends TestCase
{
    use RefreshDatabase;

    // 未ログインユーザーが / にアクセスした場合、ログイン画面へリダイレクトされる
    public function test_guest_is_redirected_to_login_from_root(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    // 一般ユーザーでログイン中に / にアクセスした場合、勤怠登録画面（/attendance）へリダイレクトされる
    public function test_general_user_is_redirected_to_attendance_from_root(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/');

        $response->assertRedirect('/attendance');
    }

    // 管理者でログイン中に / にアクセスした場合、勤怠一覧画面（/admin/attendance/list）へリダイレクトされる
    public function test_admin_is_redirected_to_admin_attendance_list_from_root(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get('/');

        $response->assertRedirect('/admin/attendance/list');
    }
}
