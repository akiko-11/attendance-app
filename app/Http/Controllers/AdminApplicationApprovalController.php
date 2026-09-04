<?php

namespace App\Http\Controllers;

use App\Services\AdminApplicationApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminApplicationApprovalController extends Controller
{
    // 修正申請の詳細画面を表示
    public function show(
        AdminApplicationApprovalService $adminApplicationApprovalService,
        int $attendance_correct_request_id
    ): View {
        $data = $adminApplicationApprovalService->getDetailData(
            $attendance_correct_request_id
        );

        return view('admin.admin-application-detail', $data);
    }

    // 修正申請を承認
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
