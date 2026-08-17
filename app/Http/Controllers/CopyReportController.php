<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Copy;
use App\Models\ExternalTeamTask;
use App\Models\PortalLink;
use App\Models\StaffMember;
use App\Models\StaffRole;
use App\Models\Task;
use App\Models\Visitor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CopyReportController extends Controller
{
    public function visitorStats(Request $request, $exhibitionId)//إحصائيات الزوار
    {
        $editionId = $request->edition_id;

        $copy = Copy::where('exhibition_id', $exhibitionId)
            ->where('id', $editionId)
            ->firstOrFail();

        // إجمالي الزوار
        $total = $copy->visitor_count;

        // توزيع حسب الأيام
        $byDay = Visitor::where('exhibition_id', $exhibitionId)
            ->selectRaw("DAYNAME(created_at) as day, COUNT(*) as count")
            ->groupBy('day')
            ->get()
            ->map(fn($d) =>
            [
                'day' => $this->arabicDay($d->day),
                'count' => $d->count
            ]);

        // توزيع حسب الاهتمامات
        $byInterest = Visitor::where('exhibition_id', $exhibitionId)
            ->selectRaw("JSON_EXTRACT(interests, '$[*]') as interests")
            ->get()
            ->flatMap(fn($v) => json_decode($v->interests ?? '[]'))
            ->countBy()
            ->map(fn($count, $name) =>
            [
                'name' => $name,
                'value' => $count
            ])
            ->values();

        return
        [
            'total' => $total,
            'byDay' => $byDay,
            'byInterest' => $byInterest
        ];
    }
    //_______________________________________
    private function arabicDay($day)
    {
        return
        [
            'Saturday' => 'السبت',
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
        ][$day] ?? $day;
    }
    //===========================================================
    public function bookingStats(Request $request, $exhibitionId)//إحصائيات الحجوزات
    {
        $editionId = $request->edition_id;

        $copy = Copy::where('exhibition_id', $exhibitionId)
            ->where('id', $editionId)
            ->firstOrFail();

        return
        [
            'booked' => $copy->booked_booths,
            'available' => $copy->available_booths,
            'revenue' => $copy->revenue,
            'pendingRequests' => $copy->pending_requests
        ];
    }
    //===========================================================
    public function staffStats($exhibitionId)//إحصائيات الموظفين
    {
        $totalStaff = PortalLink::where('exhibition_id', $exhibitionId)->count();

        // حضور الموظفين حسب الأيام
        $totalStaff = StaffMember::count();
        $attendance = AttendanceRecord::where('exhibition_id', $exhibitionId)
            ->selectRaw("DAYNAME(date) as day, COUNT(*) as count")
            ->groupByRaw("DAYNAME(date)")
            ->get()
            ->map(fn($d) =>
            [
                'day' => $this->arabicDay($d->day),
                'count' => $d->count,
                'rate' => round(($d->count / $totalStaff) * 100, 2)
            ]);

        // نسبة إنجاز المهام
        $totalTasks =
        Task::where('exhibition_id', $exhibitionId)->count() +
        ExternalTeamTask::where('external_team_id', $exhibitionId)->count();

        $completedTasks =
            Task::where('exhibition_id', $exhibitionId)->where('status', 'completed')->count() +
            ExternalTeamTask::where('external_team_id', $exhibitionId)->where('status', 'completed')->count();

        $taskCompletion = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 2)
            : 0;

        return
        [
            'totalStaff' => $totalStaff,
            'attendance' => $attendance,
            'taskCompletion' => $taskCompletion
        ];
    }
    //===========================================================
    public function revenueTimeline(Request $request, $exhibitionId)//الجدول الزمني للإيرادات
    {
        $editionId = $request->edition_id;

        $copy = Copy::where('exhibition_id', $exhibitionId)
            ->where('id', $editionId)
            ->firstOrFail();

        $months =
        [
            'فبراير', 'مارس', 'أبريل', 'مايو',
            'يونيو', 'يوليو', 'أغسطس'
        ];

        $timeline = [];
        foreach ($months as $i => $month)
        {
            $timeline[] = [
                'month' => $month,
                'revenue' => $i < 5 ? rand(30000, 450000) : null,
                'target' => rand(50000, 800000)
            ];
        }

        return $timeline;
    }
    //===========================================================
    public function editionComparisons($exhibitionId)//مقارنات الإصدارات
    {
        $copies = Copy::where('exhibition_id', $exhibitionId)->get();

        return $copies->map(function ($copy) {
            return [
                'editionId' => $copy->id . '-ed-' . $copy->year,
                'label' => $copy->copy_status === 'active'
                    ? 'النسخة الحالية ' . $copy->year
                    : 'نسخة ' . $copy->year,
                'visitors' => $copy->visitor_count,
                'revenue' => $copy->revenue,
                'bookedBooths' => $copy->booked_booths,
                'sponsorshipPercent' => $copy->sponsorship_percent
            ];
        });
    }
    //===========================================================
    public function exportPdf(Request $request, $reportType)
    {
        $exhibitionId = $request->exhibitionId;

        $data =
        [
            'reportType' => $reportType,
            'exhibitionId' => $exhibitionId,
            'generatedAt' => now()->format('Y-m-d H:i')
        ];

        $pdf = Pdf::loadView("reports.$reportType", $data);

        return $pdf->stream("$reportType-report.pdf");
    }
    //===========================================================
    //===========================================================
    //===========================================================
}
