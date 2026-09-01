<?php

namespace App\Http\Controllers;

use App\Services\AdminStaffAttendanceListService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminStaffAttendanceController extends Controller
{
    public function index(
        Request $request,
        int $id,
        AdminStaffAttendanceListService $adminStaffAttendanceListService
    ): View {
        $data = $adminStaffAttendanceListService->getListData(
            $id,
            $request->date
        );

        return view('admin.staff-attendance-list', $data);
    }
}
