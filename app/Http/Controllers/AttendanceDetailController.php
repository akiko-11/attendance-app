<?php

namespace App\Http\Controllers;

use App\Services\AttendanceDetailService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceDetailController extends Controller
{
    public function show(
        AttendanceDetailService $attendanceDetailService,
        int $id
    ): View {
        $user = Auth::user();

        $data = $attendanceDetailService->getDetailData($user, $id);

        return view('user.user-detail', [
            'user' => $user,
            'data' => $data,
        ]);
    }
}
