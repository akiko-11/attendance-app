<?php

namespace Tests\Feature\Access;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GeneralUserAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth', 'general'])
            ->get('/test/general', function () {
                return response('OK', 200);
            });
    }

    // 未ログインユーザーは一般ユーザー画面へアクセスできない
    public function test_guest_cannot_access_general_user_page(): void
    {
        $response = $this->get('/test/general');

        $response->assertRedirect('/login');
    }

    // 一般ユーザーは一般ユーザー画面へアクセスできる
    public function test_general_user_can_access_general_user_page(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get('/test/general');

        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    // 管理者が一般ユーザー画面へアクセスした場合、管理者勤怠一覧へ戻る
    public function test_admin_is_redirected_from_general_user_page(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/test/general');

        $response->assertRedirect('/admin/attendance/list');
    }
}
