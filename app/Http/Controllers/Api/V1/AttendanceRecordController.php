<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AttendanceRecordController extends Controller
{
    /**
     * 条件に一致する勤怠一覧をページネーションして取得する。
     *
     * @param  IndexAttendanceRecordRequest  $request  勤怠一覧の検索条件
     * @return AnonymousResourceCollection 勤怠情報の一覧
     */
    public function index(
        IndexAttendanceRecordRequest $request
    ): AnonymousResourceCollection {
        $validated = $request->validated();

        $perPage = $validated['per_page'] ?? 20;

        $attendanceRecords = AttendanceRecord::with(['user', 'breaks'])
            ->when(
                $validated['user_id'] ?? null,
                fn ($query, $userId) => $query->where('user_id', $userId)
            )
            ->when(
                $validated['date'] ?? null,
                fn ($query, $date) => $query->whereDate('date', $date)
            )
            ->when(
                $validated['month'] ?? null,
                function ($query, $month) {
                    [$year, $monthNumber] = explode('-', $month);

                    $query->whereYear('date', $year)
                        ->whereMonth('date', $monthNumber);
                }
            )
            ->latest('date')
            ->paginate($perPage);

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    /**
     * 指定された勤怠情報の詳細を取得する。
     *
     * @param  AttendanceRecord  $attendanceRecord  取得対象の勤怠情報
     * @return AttendanceRecordResource 勤怠情報の詳細
     */
    public function show(
        AttendanceRecord $attendanceRecord
    ): AttendanceRecordResource {
        $attendanceRecord->load([
            'user',
            'applications',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 認証ユーザーの勤怠情報を新規登録する。
     *
     * @param  StoreAttendanceRecordRequest  $request  登録する勤怠情報
     * @return JsonResponse 登録した勤怠情報
     */
    public function store(
        StoreAttendanceRecordRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $attendanceRecord = $request->user()
            ->attendanceRecords()
            ->create($validated);

        $attendanceRecord->load([
            'user',
            'breaks',
        ]);

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 指定された勤怠情報を更新する。
     *
     * @param  UpdateAttendanceRecordRequest  $request  更新する勤怠情報
     * @param  AttendanceRecord  $attendanceRecord  更新対象の勤怠情報
     * @return AttendanceRecordResource 更新後の勤怠情報
     */
    public function update(
        UpdateAttendanceRecordRequest $request,
        AttendanceRecord $attendanceRecord
    ): AttendanceRecordResource {
        $this->authorize('update', $attendanceRecord);

        $attendanceRecord->update(
            $request->validated()
        );

        $attendanceRecord->load([
            'user',
            'breaks',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 指定された勤怠情報を削除する。
     *
     * @param  AttendanceRecord  $attendanceRecord  削除対象の勤怠情報
     * @return Response 空のレスポンス
     */
    public function destroy(
        AttendanceRecord $attendanceRecord
    ): Response {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
