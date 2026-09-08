<?php

namespace App\Http\Controllers;

use App\Services\AdminAttendanceDetailService;
use Illuminate\View\View;

class AdminAttendanceDetailController extends Controller
{
    public function show(
        AdminAttendanceDetailService $adminAttendanceDetailService,
        int $id
    ): View {
        $data = $adminAttendanceDetailService->getDetailData($id);

        return view('admin.admin-detail', [
            'user' => $data['user'],
            'attendanceRecord' => $data['attendanceRecord'],
        ]);
    }
}
