<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    // 名前が未入力の場合、バリデーションに失敗する
    public function test_name_is_required_for_registration(): void
    {
        $data = [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    // メールアドレスが未入力の場合、バリデーションに失敗する
    public function test_email_is_required_for_registration(): void
    {
        $data = [
            'name' => 'テスト 太郎',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // メールアドレスがメール形式ではない場合、バリデーションに失敗する
    public function test_email_must_be_valid_format_for_registration(): void
    {
        $data = [
            'name' => 'テスト 太郎',
            'email' => 'invalid-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください',
        ]);
    }

    // パスワードが8文字未満の場合、バリデーションに失敗する
    public function test_password_must_be_at_least_8_characters_for_registration(): void
    {
        $data = [
            'name' => 'テスト 太郎',
            'email' => 'test@example.com',
            'password' => 'error',
            'password_confirmation' => 'error',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    // パスワードが一致しない場合、バリデーションに失敗する
    public function test_password_confirmation_does_not_match_for_registration(): void
    {
        $data = [
            'name' => 'テスト 太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'error_password',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    // パスワードが未入力の場合、バリデーションに失敗する
    public function test_password_is_required_for_registration(): void
    {
        $data = [
            'name' => 'テスト 太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ];

        $response = $this->post('/register', $data);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // 正しく内容が入力されていた場合、
    // バリデーションに成功し、正常に登録される
    public function test_user_can_register_with_valid_data(): void
    {
        $data = [
            'name' => 'テスト 太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->post('/register', $data);

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('users', [
            'name' => 'テスト 太郎',
            'email' => 'test@example.com',
            'admin_status' => false,
        ]);

        $this->assertAuthenticated();
    }
}
