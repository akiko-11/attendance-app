<?php

namespace Tests\Feature\Application;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationDetailNavigationTest extends TestCase
{
    use RefreshDatabase;

    // 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
    public function test_application_detail_redirects_to_related_attendance(): void
    {
        // 一般ユーザーと勤怠を作成
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'id' => 100,
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        // 本人として勤怠の修正申請を送信
        $storeResponse = $this->actingAs($user)
            ->post("/attendance/{$attendanceRecord->id}", [
                'new_clock_in' => '09:10',
                'new_clock_out' => '18:10',
                'new_break_in' => ['12:15', ''],
                'new_break_out' => ['13:15', ''],
                'comment' => '打刻ミスのため',
            ]);

        $storeResponse->assertSessionHasNoErrors();
        $storeResponse->assertRedirect(
            "/attendance/detail/{$attendanceRecord->id}"
        );

        // 保存された申請を取得
        $application = $attendanceRecord->attendanceCorrectionRequests()
            ->sole();

        // 申請IDと勤怠IDが異なることを確認
        $this->assertNotEquals($attendanceRecord->id, $application->id);

        // 申請一覧を開き、対象申請の詳細リンクを確認
        $listResponse = $this->get('/stamp_correction_request/list');

        $listResponse->assertStatus(200);
        $listResponse->assertSee(
            'href="'.url('/application/'.$application->id).'"',
            false
        );

        // 申請一覧の詳細リンク先へGET
        $response = $this->get("/application/{$application->id}");

        // 申請に紐づく勤怠詳細へ転送されることを確認
        $detailUrl = "/attendance/detail/{$attendanceRecord->id}";
        $response->assertRedirect($detailUrl);

        // 転送先の勤怠詳細を取得
        $detailResponse = $this->get($detailUrl);

        // 対象の勤怠詳細と申請内容が表示されることを確認
        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('user.user-detail');
        $detailResponse->assertSee('value="2026年"', false);
        $detailResponse->assertSee('value="9月4日"', false);
        $detailResponse->assertSee('value="09:10"', false);
        $detailResponse->assertSee('value="18:10"', false);
        $detailResponse->assertSee('value="打刻ミスのため"', false);
    }

    // 他人の修正申請から勤怠詳細へ遷移できない
    public function test_user_cannot_open_another_users_application(): void
    {
        // 一般ユーザーを2人作成
        $targetUser = User::factory()->create([
            'name' => '本人',
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'name' => '他人',
            'admin_status' => false,
        ]);

        // 相手の勤怠と修正申請を作成
        $otherAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        // 相手の勤怠に修正申請を作成
        $otherApplication = $otherAttendanceRecord
            ->attendanceCorrectionRequests()->create([
                'user_id' => $otherUser->id,
                'new_date' => $otherAttendanceRecord->date,
                'new_clock_in' => '09:40',
                'new_clock_out' => '18:40',
                'comment' => '打刻ミスのため',
                'approval_status' => false,
            ]);

        // 本人として、相手の申請IDへGET
        $response = $this->actingAs($targetUser)
            ->get("/application/{$otherApplication->id}");

        // 404で拒否されることを確認
        $response->assertNotFound();
    }

    // 未ログインでは申請詳細からログイン画面へ遷移する
    public function test_guest_is_redirected_to_login_from_application(): void
    {
        // 一般ユーザー・勤怠・修正申請を作成
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        $application = $attendanceRecord->attendanceCorrectionRequests()->create([
            'user_id' => $user->id,
            'new_date' => $attendanceRecord->date,
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        // 未ログインで申請詳細へGET
        $response = $this->get("/application/{$application->id}");

        // ログイン画面へリダイレクトされることを確認
        $response->assertRedirect('/login');
    }
}
