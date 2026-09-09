<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // AdminMiddleware確認用のテストRoute
        Route::middleware(['auth', 'admin'])
            ->get('/test/admin', function () {
                return response('OK', 200);
            });
    }

    // 未ログインユーザーは管理者画面へアクセスできない
    public function test_guest_cannot_access_admin_page(): void
    {
        $response = $this->get('/test/admin');

        $response->assertRedirect('/login');
    }

    // 一般ユーザーが管理者画面へアクセスした場合、勤怠登録画面へ戻る
    public function test_general_user_is_redirected_from_admin_page(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get('/test/admin');

        $response->assertRedirect('/attendance');
    }

    // 管理者は管理者画面へアクセスできる
    public function test_admin_can_access_admin_page(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/test/admin');

        $response->assertStatus(200);
        $response->assertSee('OK');
    }

    // 一般ユーザーが実際の管理者画面へアクセスすると勤怠登録画面へ戻る
    public function test_general_user_is_redirected_from_actual_admin_page(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/attendance/list');

        $response->assertRedirect('/attendance');
    }
}
