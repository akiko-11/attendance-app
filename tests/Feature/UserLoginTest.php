<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    // メールアドレスが未入力の場合、バリデーションに失敗する
    public function test_email_is_required_for_login(): void
    {
        $data = [
            'email' => '',
            'password' => 'password',
        ];

        $response = $this->post('/login', $data);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワードが未入力の場合、バリデーションに失敗する
    public function test_password_is_required_for_login(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => '',
        ];

        $response = $this->post('/login', $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // 登録内容と一致しない場合、ログインに失敗する
    public function test_login_fails_when_credentials_do_not_match(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'admin_status' => false,
        ]);

        $data = [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ];

        $response = $this->post('/login', $data);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    // 管理者は一般ユーザー用ログインからログインできない
    public function test_admin_user_cannot_login_from_general_user_login(): void
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

        $response = $this->post('/login', $data);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);

        $this->assertGuest();
    }

    // 正しい認証情報の場合、一般ユーザーとしてログインできる
    public function test_general_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'admin_status' => false,
        ]);

        $data = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $response = $this->post('/login', $data);

        $response->assertRedirect('/attendance');
        $this->assertAuthenticatedAs($user);
    }

    // ログイン済みの一般ユーザーが正常にログアウトできる
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user);

        $this->post('/logout');

        $this->assertGuest();
    }
}
