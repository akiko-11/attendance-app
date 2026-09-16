<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AttendanceRecordController extends Controller
{
    // 勤怠一覧取得
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

    // 勤怠詳細取得
    public function show(
        AttendanceRecord $attendanceRecord
    ): AttendanceRecordResource {
        $attendanceRecord->load([
            'user',
            'applications',
        ]);

        return new AttendanceRecordResource($attendanceRecord);
    }

    // 勤怠新規登録
    public function store(
        StoreAttendanceRecordRequest $request
    ) {
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

    // 勤怠更新
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

    // 勤怠削除
    public function destroy(
        AttendanceRecord $attendanceRecord
    ): Response {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
