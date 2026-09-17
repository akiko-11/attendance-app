<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportAttendanceRequest;
use App\Services\AdminStaffAttendanceListService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffAttendanceController extends Controller
{
    /**
     * 指定されたスタッフの月次勤怠一覧を取得して表示する。
     *
     * @param  Request  $request  表示対象月の情報を含むリクエスト
     * @param  int  $id  スタッフのユーザーID
     * @param  AdminStaffAttendanceListService  $adminStaffAttendanceListService  スタッフの勤怠一覧を取得するサービス
     * @return View スタッフ別勤怠一覧画面
     */
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

    /**
     * 指定されたスタッフの月次勤怠情報をCSV形式で出力する。
     *
     * @param  ExportAttendanceRequest  $request  CSV出力条件を含むリクエスト
     * @param  AdminStaffAttendanceListService  $adminStaffAttendanceListService  スタッフの勤怠一覧を取得するサービス
     * @return StreamedResponse 勤怠情報のCSVダウンロードレスポンス
     */
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
