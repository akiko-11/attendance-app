<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    // 勤怠詳細の名前がログインユーザーの氏名になっている
    public function test_attendance_detail_displays_logged_in_user_name(): void
    {
        // 一般ユーザーと勤怠を作成
        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        $attendance = $user->attendanceRecords()->create([
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->id}");

        // user.user-detail が使用されていることを確認
        $response->assertStatus(200);
        $response->assertViewIs('user.user-detail');

        // 名前のinputに本人の氏名が表示されていることを確認
        $response->assertSee('value="ユーザー"', false);
    }

    // 勤怠詳細の日付が選択した勤怠の日付になっている
    public function test_attendance_detail_displays_selected_date(): void
    {
        // 一般ユーザーを作成
        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        // 同じユーザーの勤怠を異なる日付で2件作成
        $attendance1 = $user->attendanceRecords()->create([
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 片方の勤怠IDを指定
        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance1->id}");

        $response->assertStatus(200);

        // 選択した勤怠の年・月日が表示されることを確認
        $response->assertSee('value="2026年"', false);
        $response->assertSee('value="9月4日"', false);
        $response->assertDontSee('value="9月5日"', false);
    }

    // 勤怠詳細の出勤・退勤時刻が本人の打刻と一致している
    public function test_attendance_detail_displays_recorded_clock_times(): void
    {
        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        // 出勤09:10:00・退勤18:15:00の勤怠を作成
        $attendance = $user->attendanceRecords()->create([
            'date' => '2026-09-04',
            'clock_in' => '09:10:00',
            'clock_out' => '18:15:00',
        ]);

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);

        // 出勤・退勤がH:i形式で表示されることを確認
        $response->assertSee('value="09:10"', false);
        $response->assertSee('value="18:15"', false);
    }

    // 「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_attendance_detail_displays_all_recorded_breaks(): void
    {
        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        $attendance = $user->attendanceRecords()->create([
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // その勤怠に休憩を3件作成
        $attendance->breaks()->create([
            'break_in' => '10:00:00',
            'break_out' => '10:15:00',
        ]);

        $attendance->breaks()->create([
            'break_in' => '11:00:00',
            'break_out' => '11:15:00',
        ]);

        $attendance->breaks()->create([
            'break_in' => '14:00:00',
            'break_out' => '14:15:00',
        ]);

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);

        // 3件分の休憩開始・終了時刻が表示されることを確認
        $response->assertSee('value="10:00"', false);
        $response->assertSee('value="10:15"', false);
        $response->assertSee('value="11:00"', false);
        $response->assertSee('value="11:15"', false);
        $response->assertSee('value="14:00"', false);
        $response->assertSee('value="14:15"', false);

        // 末尾に追加用の空の休憩欄が表示される
        $response->assertSeeText('休憩4');
        $response->assertSee('name="new_break_in[3]" value=""', false);
        $response->assertSee('name="new_break_out[3]" value=""', false);

        // さらに次の休憩欄は表示されない
        $response->assertDontSee('name="new_break_in[4]"', false);
        $response->assertDontSee('name="new_break_out[4]"', false);
    }

    // 勤怠一覧の詳細リンクから対象の勤怠詳細へ遷移できる
    public function test_attendance_list_detail_link_opens_selected_attendance(): void
    {
        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        $attendance = $user->attendanceRecords()->create([
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 本人として、その勤怠がある月の一覧へGET
        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-09');

        // 一覧に対象勤怠の詳細リンクが表示されていることを確認
        $response->assertStatus(200);
        $response->assertSee(
            'href="'.url('/attendance/'.$attendance->id).'"',
            false
        );

        // 詳細リンク先へGETし、仕様のURLへの転送を確認
        $detailUrl = "/attendance/detail/{$attendance->id}";

        $redirectResponse = $this->get("/attendance/{$attendance->id}");
        $redirectResponse->assertRedirect($detailUrl);

        // 転送先で対象の勤怠詳細が表示されることを確認
        $detailResponse = $this->get($detailUrl);

        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('user.user-detail');
        $detailResponse->assertSee('value="2026年"', false);
        $detailResponse->assertSee('value="9月4日"', false);
    }
}
