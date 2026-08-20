<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\StaffMember;
use App\Models\Exhibition;
use App\Services\NotificationService;

use App\Http\Requests\StoreAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;

class AttendanceController extends Controller
{
    public function index()
    {
        $records = AttendanceRecord::with('staff')
            ->when(request('exhibition_id'), fn($q) =>
                $q->where('exhibition_id', request('exhibition_id'))
            )
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return AttendanceRecordResource::collection($records);
    }
    //===========================================
    public function store(StoreAttendanceRecordRequest $request)
    {
        $data = $request->validated();

        // حساب ساعات العمل إذا كان check_in و check_out موجودين
        if (!empty($data['check_in']) && !empty($data['check_out']))
        {
            $start = strtotime($data['check_in']);

            $end = strtotime($data['check_out']);
            $data['hours_worked'] = round(($end - $start) / 3600, 2);
        }

        $record = AttendanceRecord::create($data);

        $exhibition = Exhibition::find($record->exhibition_id);
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition, 'تم تسجيل حضور موظف', 'تم تسجيل حركة حضور أو انصراف جديدة.', 'attendance', 'admin.attendance',
                ['attendanceId' => (string) $record->id], '/staff/attendance', ['admin.staff']
            );
        }

        return new AttendanceRecordResource($record);
    }
    //===========================================
}
