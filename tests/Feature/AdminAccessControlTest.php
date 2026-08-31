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

    // 一般ユーザーは管理者画面へアクセスできない
    public function test_general_user_cannot_access_admin_page(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get('/test/admin');

        $response->assertStatus(403);
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
}
