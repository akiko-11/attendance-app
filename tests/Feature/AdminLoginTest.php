<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    // メールアドレスが未入力の場合、バリデーションに失敗する
    public function test_email_is_required_for_login(): void
    {
        $data = [
            'email' => '',
            'password' => 'password',
        ];

        $response = $this->post('/admin/login', $data);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワードが未入力の場合、バリデーションに失敗する
    public function test_password_is_required_for_login(): void
    {
        $data = [
            'email' => 'admin@example.com',
            'password' => '',
        ];

        $response = $this->post('/admin/login', $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // 登録内容と一致しない場合、ログインに失敗する
    public function test_login_fails_when_credentials_do_not_match(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'admin_status' => true,
        ]);

        $data = [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ];

        $response = $this->post('/admin/login', $data);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    // 一般ユーザーは管理者用ログインからログインできない
    public function test_general_user_cannot_login_from_admin_login(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'admin_status' => false,
        ]);

        $data = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $response = $this->post('/admin/login', $data);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);

        $this->assertGuest();
    }

    // 正しい認証情報の場合、管理者としてログインできる
    public function test_admin_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'admin_status' => true,
        ]);

        $data = [
            'email' => 'admin@example.com',
            'password' => 'password',
        ];

        $response = $this->post('/admin/login', $data);

        $response->assertRedirect('/admin/attendance/list');
        $this->assertAuthenticatedAs($user);
    }

    // ログイン済みの管理者が正常にログアウトできる
    public function test_authenticated_admin_can_logout(): void
    {
        $user = User::factory()->create([
            'admin_status' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertGuest();
    }
}
