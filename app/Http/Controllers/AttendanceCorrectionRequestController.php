<?php

namespace App\Http\Controllers;

use App\Services\AdminApplicationListService;
use App\Services\ApplicationListService;
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
}
