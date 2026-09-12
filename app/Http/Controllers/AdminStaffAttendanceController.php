<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportAttendanceRequest;
use App\Services\AdminStaffAttendanceListService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    // CSV出力
    public function export(
        ExportAttendanceRequest $request,
        AdminStaffAttendanceListService $adminStaffAttendanceListService
    ): StreamedResponse {
        $validated = $request->validated();

        $userId = (int) $validated['user_id'];
        $yearMonth = $validated['year_month'];

        $data = $adminStaffAttendanceListService->getListData(
            $userId,
            $yearMonth
        );

        $attendanceRecords = $data['formattedAttendanceRecords'];

        return response()->streamDownload(function () use ($attendanceRecords) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '日付',
                '出勤',
                '退勤',
                '休憩',
                '合計',
            ]);

            foreach ($attendanceRecords as $record) {
                fputcsv($handle, [
                    $record['date'],
                    $record['clock_in'],
                    $record['clock_out'],
                    $record['total_break_time'],
                    $record['total_time'],
                ]);
            }

            fclose($handle);
        }, 'attendance.csv');
    }
}
