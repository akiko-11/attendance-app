<?php

namespace App\Http\Controllers;

use App\Services\AdminApplicationApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminApplicationApprovalController extends Controller
{
    /**
     * 指定された勤怠修正申請の詳細を取得して表示する。
     *
     * @param  AdminApplicationApprovalService  $adminApplicationApprovalService  修正申請の詳細を取得するサービス
     * @param  int  $attendance_correct_request_id  修正申請ID
     * @return View 修正申請の詳細画面
     */
    public function show(
        AdminApplicationApprovalService $adminApplicationApprovalService,
        int $attendance_correct_request_id
    ): View {
        $data = $adminApplicationApprovalService->getDetailData(
            $attendance_correct_request_id
        );

        return view('admin.admin-application-detail', $data);
    }

    /**
     * 指定された勤怠修正申請を承認する。
     *
     * @param  AdminApplicationApprovalService  $adminApplicationApprovalService  修正申請の承認処理を行うサービス
     * @param  int  $attendance_correct_request_id  修正申請ID
     * @return RedirectResponse 承認後の修正申請詳細画面へのリダイレクト
     */
    public function approve(
        AdminApplicationApprovalService $adminApplicationApprovalService,
        int $attendance_correct_request_id
    ): RedirectResponse {
        $adminApplicationApprovalService->approve(
            $attendance_correct_request_id
        );

        // 再表示すると、Blade側で「承認済み」に切り替わる
        return redirect(
            '/stamp_correction_request/approve/'
            .$attendance_correct_request_id
        );
    }
}
