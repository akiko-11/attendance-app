<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Services\AdminApplicationListService;
use App\Services\ApplicationListService;
use App\Services\AttendanceCorrectionRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceCorrectionRequestController extends Controller
{
    // 申請一覧画面
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

    // 申請一覧から対象の勤怠詳細へ遷移
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

    // 勤怠修正申請を保存
    public function store(
        StoreAttendanceCorrectionRequest $request,
        AttendanceCorrectionRequestService $attendanceCorrectionRequestService,
        int $id
    ): RedirectResponse {
        $user = $request->user();

        // Serviceへユーザー・勤怠ID・検証済みデータを渡して保存
        $attendanceCorrectionRequestService->store(
            $user,
            $id,
            $request->validated()
        );

        // 同じ勤怠の詳細画面へリダイレクト
        return redirect("/attendance/detail/{$id}");
    }
}
