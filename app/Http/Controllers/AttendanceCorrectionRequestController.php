<?php

namespace App\Http\Controllers;

use App\Services\ApplicationListService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceCorrectionRequestController extends Controller
{
    // 申請一覧画面
    public function index(ApplicationListService $applicationListService): View
    {
        $user = Auth::user();

        $data = $applicationListService->getListData($user);

        return view('user.user-application-list', [
            'user' => $user,
            ...$data,
        ]);
    }
}
