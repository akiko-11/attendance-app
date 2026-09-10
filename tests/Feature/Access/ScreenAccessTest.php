<?php

namespace Tests\Feature\Access;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenAccessTest extends TestCase
{
    use RefreshDatabase;

    // 一般ユーザーが管理者画面へ直接アクセスすると勤怠登録画面へ戻る
    public function test_general_user_is_redirected_from_admin_pages(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $urls = [
            '/admin/attendance/list',
            '/admin/staff/list',
        ];

        foreach ($urls as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertRedirect('/attendance');
        }
    }

    // 管理者が一般ユーザー画面へ直接アクセスすると今日の勤怠一覧へ戻る
    public function test_admin_is_redirected_from_general_user_pages(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $urls = [
            '/attendance',
            '/attendance/list',
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertRedirect('/admin/attendance/list');
        }
    }

    // 未ログインユーザーが保護画面へ直接アクセスすると一般ログイン画面へ戻る
    public function test_guest_is_redirected_to_login_page(): void
    {
        $urls = [
            '/attendance',
            '/attendance/list',
            '/admin/attendance/list',
            '/admin/staff/list',
        ];

        foreach ($urls as $url) {
            $this->get($url)
                ->assertRedirect('/login');
        }
    }

    // 一般ユーザーが管理者の勤怠詳細へ直接アクセスすると勤怠登録画面へ戻る
    public function test_general_user_is_redirected_from_admin_attendance_detail(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)
            ->get('/admin/attendance/1')
            ->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertStatus(200);
    }

    // 管理者が一般ユーザー専用画面・打刻処理へアクセスすると管理者勤怠一覧へ戻る
    public function test_admin_is_redirected_from_general_user_detail_and_stamp(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $this->actingAs($admin)
            ->get('/attendance/detail/1')
            ->assertRedirect('/admin/attendance/list');

        $this->post('/attendance', [
            'action' => 'clock_in',
        ])->assertRedirect('/admin/attendance/list');

        $this->get('/admin/attendance/list')
            ->assertStatus(200);
    }

    // 未ログインユーザーが打刻処理へ直接アクセスするとログイン画面へ戻る
    public function test_guest_is_redirected_from_stamp_post_to_login(): void
    {
        auth()->logout();

        $this->post('/attendance', [
            'action' => 'clock_in',
        ])->assertRedirect('/login');

        $this->get('/login')
            ->assertStatus(200);
    }
}
