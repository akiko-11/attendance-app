<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionFormRequest;
use App\Services\AdminApplicationListService;
use App\Services\AdminAttendanceUpdateService;
use App\Services\ApplicationListService;
use App\Services\AttendanceCorrectionRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceCorrectionRequestController extends Controller
{
    /**
     * 認証ユーザーの権限に応じた修正申請一覧を表示する。
     *
     * @param  ApplicationListService  $applicationListService  一般ユーザーの申請一覧を取得するサービス
     * @param  AdminApplicationListService  $adminApplicationListService  管理者用の申請一覧を取得するサービス
     * @return View 修正申請一覧画面
     */
    public function index(
        ApplicationListService $applicationListService,
        AdminApplicationListService $adminApplicationListService
    ): View {
        $user = Auth::user();

        if ($user->admin_status) {
            // 管理者の場合
            $data = $adminApplicationListService->getListData();

            return view('admin.admin-application-list', $data);
        }

        // 一般ユーザーの場合
        $data = $applicationListService->getListData($user);

        return view('user.user-application-list', [
            'user' => $user,
            ...$data,
        ]);
    }

    /**
     * 指定された修正申請に紐づく勤怠詳細画面へ遷移する。
     *
     * @param  int  $id  修正申請ID
     * @return RedirectResponse 勤怠詳細画面へのリダイレクト
     */
    public function show(int $id): RedirectResponse
    {
        $user = Auth::user();

        // 本人の修正申請から、申請IDに一致するものを取得
        $application = $user->attendanceCorrectionRequests()
            ->findOrFail($id);

        // 申請に紐づく勤怠の詳細画面へリダイレクト
        return redirect(
            "/attendance/detail/{$application->attendance_record_id}"
        );
    }

    /**
     * 勤怠修正を処理し、ユーザー権限に応じて修正または申請を保存する。
     *
     * @param  AttendanceCorrectionFormRequest  $request  勤怠修正内容のリクエスト
     * @param  AttendanceCorrectionRequestService  $attendanceCorrectionRequestService  修正申請を保存するサービス
     * @param  AdminAttendanceUpdateService  $adminAttendanceUpdateService  管理者による勤怠修正を行うサービス
     * @param  int  $id  勤怠情報ID
     * @return RedirectResponse 処理後の勤怠詳細画面へのリダイレクト
     */
    public function store(
        AttendanceCorrectionFormRequest $request,
        AttendanceCorrectionRequestService $attendanceCorrectionRequestService,
        AdminAttendanceUpdateService $adminAttendanceUpdateService,
        int $id
    ): RedirectResponse {
        $user = $request->user();
        $data = $request->validated();

        if ($user->admin_status) {
            // 管理者は勤怠を直接修正
            $adminAttendanceUpdateService->update($id, $data);

            return redirect("/admin/attendance/{$id}");
        }

        // 一般ユーザーは修正申請を保存
        $attendanceCorrectionRequestService->store(
            $user,
            $id,
            $data
        );

        return redirect("/attendance/detail/{$id}");
    }
}
