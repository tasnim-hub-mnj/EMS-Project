<?php

namespace App\Http\Controllers;

use App\Exports\OrganizerReportExport;
use App\Models\AttendanceRecord;
use App\Models\BoothBooking;
use App\Models\Copy;
use App\Models\ExternalTeamTask;
use App\Models\PortalLink;
use App\Models\StaffMember;
use App\Models\StaffRole;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\Visitor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CopyReportController extends Controller
{
    public function visitorStats(Request $request, $exhibitionId)//إحصائيات الزوار
    {
        $copy = $this->copyForEdition($exhibitionId, $request->edition_id);
        $ticketQuery = $this->ticketsForCopy($exhibitionId, $copy);

        $total = (clone $ticketQuery)->count();

        // توزيع حسب الأيام
        $byDay = (clone $ticketQuery)
            ->get(['created_at'])
            ->groupBy(fn ($ticket) => Carbon::parse($ticket->created_at)->dayName)
            ->map(fn ($tickets, $day) => [
                'day' => $this->arabicDay($day),
                'count' => $tickets->count(),
            ])
            ->values();

        // توزيع حسب الاهتمامات
        $visitorIds = (clone $ticketQuery)
            ->pluck('visitor_id');

        $interestCounts = Visitor::whereIn('id', $visitorIds)
            ->get()
            ->flatMap(fn($v) => is_array($v->interests) ? $v->interests : [])
            ->countBy();
        $interestTotal = max(1, $interestCounts->sum());
        $byInterest = $interestCounts->map(fn($count, $name) =>
            [
                'name' => $name,
                'value' => round(($count / $interestTotal) * 100, 2)
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
        $copy = $this->copyForEdition($exhibitionId, $request->edition_id);
        $bookings = BoothBooking::where('copy_id', $copy->id);
        $booked = (clone $bookings)->whereIn('status', ['approved', 'finished'])->count();

        return
        [
            'booked' => $booked,
            'available' => max(0, $copy->total_booths - $booked),
            'revenue' => (float) (clone $bookings)->whereIn('status', ['approved', 'finished'])->sum('total_price'),
            'pendingRequests' => (clone $bookings)->where('status', 'pending')->count()
        ];
    }
    //===========================================================
    public function staffStats(Request $request, $exhibitionId)//إحصائيات الموظفين
    {
        $copy = $request->edition_id ? $this->copyForEdition($exhibitionId, $request->edition_id) : null;
        $totalStaff = PortalLink::where('exhibition_id', $exhibitionId)->distinct('staff_id')->count('staff_id');
        $attendanceQuery = AttendanceRecord::where('exhibition_id', $exhibitionId);
        if ($copy) {
            $attendanceQuery->whereBetween('date', [$copy->start_date, $copy->end_date]);
        }

        // حضور الموظفين حسب الأيام
        $attendance = $attendanceQuery
            ->get(['date', 'staff_id'])
            ->groupBy(fn ($record) => Carbon::parse($record->date)->dayName)
            ->map(fn ($records, $day) => [
                'day' => $this->arabicDay($day),
                'count' => $records->pluck('staff_id')->unique()->count(),
                'rate' => $totalStaff > 0
                    ? round(($records->pluck('staff_id')->unique()->count() / $totalStaff) * 100, 2)
                    : 0,
            ])
            ->values();

        // نسبة إنجاز المهام
        $totalTasks = Task::where('exhibition_id', $exhibitionId)->count();

        $completedTasks = Task::where('exhibition_id', $exhibitionId)->where('status', 'completed')->count();

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
        $copy = $this->copyForEdition($exhibitionId, $request->edition_id);
        $start = Carbon::parse($copy->start_date)->startOfMonth();
        $end = Carbon::parse($copy->end_date)->startOfMonth();
        $monthCount = max(1, $start->diffInMonths($end) + 1);
        $target = (float) $copy->expected_revenue / $monthCount;
        $bookings = BoothBooking::where('copy_id', $copy->id)
            ->whereIn('status', ['approved', 'finished'])
            ->get(['total_price', 'approved_at', 'booked_at', 'created_at']);
        $revenueByMonth = $bookings->groupBy(function ($booking) {
            return Carbon::parse($booking->approved_at ?? $booking->booked_at ?? $booking->created_at)->format('Y-m');
        });

        return collect(CarbonPeriod::create($start, '1 month', $end))->map(function (Carbon $month) use ($revenueByMonth, $target) {
            $key = $month->format('Y-m');
            return [
                'month' => $this->arabicMonth($month->month),
                'revenue' => (float) ($revenueByMonth->get($key, collect())->sum('total_price')),
                'target' => $target,
            ];
        })->values();
    }
    //===========================================================
    public function editionComparisons($exhibitionId)//مقارنات الإصدارات
    {
        $copies = Copy::where('exhibition_id', $exhibitionId)->get();

        return $copies->map(function ($copy)
        {
            $bookings = BoothBooking::where('copy_id', $copy->id);
            $confirmedBookings = (clone $bookings)->whereIn('status', ['approved', 'finished']);
            $visitors = $this->ticketsForCopy($copy->exhibition_id, $copy)->count();
            $bookedBooths = (clone $confirmedBookings)->count();

            return [
                'editionId' => $copy->id . '-ed-' . $copy->year,
                'label' => $copy->copy_status === 'active'
                    ? 'النسخة الحالية ' . $copy->year
                    : 'نسخة ' . $copy->year,
                'visitors' => $visitors,
                'revenue' => (float) $confirmedBookings->sum('total_price'),
                'bookedBooths' => $bookedBooths,
                'sponsorshipPercent' => $copy->sponsorship_percent
            ];
        });
    }
    //===========================================================
    public function exportPdf(Request $request, $reportType)
    {
        $exhibitionId = $request->exhibitionId;
        $copy = $this->copyForEdition($exhibitionId, $request->edition_id);
        $data = $this->reportData($request, $exhibitionId, $copy);
        $pdf = Pdf::loadView('reports.archive', $data);

        return $pdf->stream("$reportType-report.pdf");
    }

    public function exportExcel(Request $request, $reportType)
    {
        $exhibitionId = $request->exhibitionId;
        $editionIds = $request->input('edition_ids', []);
        $editionIds = is_array($editionIds) ? $editionIds : [$editionIds];
        if (count($editionIds) === 0 || $editionIds === [null]) {
            $editionIds = [$request->edition_id];
        }

        $rows = [];
        foreach (array_values(array_filter($editionIds)) as $editionId) {
            $editionRequest = Request::create('/', 'GET', ['edition_id' => $editionId]);
            $copy = $this->copyForEdition($exhibitionId, $editionId);
            $data = $this->reportData($editionRequest, $exhibitionId, $copy);
            $rows = array_merge($rows, $this->reportRows($data), [[]]);
        }

        return Excel::download(
            new OrganizerReportExport($rows),
            "$reportType-report.xlsx"
        );
    }

    private function reportData(Request $request, $exhibitionId, Copy $copy): array
    {
        return [
            'copy' => $copy,
            'visitorStats' => $this->visitorStats($request, $exhibitionId),
            'bookingStats' => $this->bookingStats($request, $exhibitionId),
            'revenueTimeline' => $this->revenueTimeline($request, $exhibitionId),
            'generatedAt' => now()->format('Y-m-d H:i'),
        ];
    }

    private function reportRows(array $data): array
    {
        $rows = [
            ['تقرير النسخة', $data['copy']->year],
            ['الفترة', $data['copy']->start_date . ' - ' . $data['copy']->end_date],
            [],
            ['الإحصائية', 'القيمة'],
            ['الزوار', $data['visitorStats']['total']],
            ['الأجنحة المحجوزة', $data['bookingStats']['booked']],
            ['الأجنحة المتاحة', $data['bookingStats']['available']],
            ['الإيرادات', $data['bookingStats']['revenue']],
            ['الطلبات المعلقة', $data['bookingStats']['pendingRequests']],
            [],
            ['الزوار حسب اليوم', 'العدد'],
        ];

        foreach ($data['visitorStats']['byDay'] as $item) {
            $rows[] = [$item['day'], $item['count']];
        }
        $rows[] = [];
        $rows[] = ['الاهتمام', 'النسبة'];
        foreach ($data['visitorStats']['byInterest'] as $item) {
            $rows[] = [$item['name'], $item['value'] . '%'];
        }
        $rows[] = [];
        $rows[] = ['الشهر', 'الإيراد', 'المستهدف'];
        foreach ($data['revenueTimeline'] as $item) {
            $rows[] = [$item['month'], $item['revenue'], $item['target']];
        }

        return $rows;
    }

    private function copyForEdition($exhibitionId, ?string $editionId): Copy
    {
        if (!$editionId) {
            abort(response()->json([
                'success' => false,
                'message' => 'edition_id is required'
            ], 422));
        }

        $copyId = str_contains($editionId, '-ed-')
            ? explode('-ed-', $editionId, 2)[0]
            : $editionId;
        $copy = Copy::where('exhibition_id', $exhibitionId)->whereKey($copyId)->first();

        if (!$copy) {
            abort(response()->json([
                'success' => false,
                'message' => 'The requested edition does not belong to this exhibition.'
            ], 403));
        }

        return $copy;
    }

    private function ticketsForCopy($exhibitionId, Copy $copy)
    {
        return Ticket::where('exhibition_id', $exhibitionId)
            ->whereBetween('created_at', [
                Carbon::parse($copy->start_date)->startOfDay(),
                Carbon::parse($copy->end_date)->endOfDay(),
            ]);
    }

    private function arabicMonth(int $month): string
    {
        return [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ][$month];
    }
    //===========================================================
    //===========================================================
    //===========================================================
}
