<?php
//events report
//لكل جناح, يُنشأ عند بداية فعالية في هذا الحجز

use App\Models\BoothBooking;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\InvestorEventReports;
use Illuminate\Support\Carbon;

$bookings = BoothBooking::where('status', 'approved')->get();
$today = Carbon::today()->format('Y-m-d');

foreach ($bookings as $booking)
{
    $start = Carbon::parse($booking->start_date);
    $end = Carbon::parse($booking->end_date);
    $startDate = $start->format('Y-m-d');
    $endDate = $end->format('Y-m-d');

    if($startDate <= $today && $endDate >= $today)
    {
        $booth = $booking->booth;
        //__________________________________________________________
        $events = Event::where('booth_booking_id',$booking->id)->get();
        $event_ids = $events->pluck('id');

        $registered_count = 0;
        $scanned_count = 0;
        $evaluation = 0;
        foreach($events as $event)
        {
            $registered_count += $event->registered_count ?? 0;//اجمالي عدد المسجلين
            $scanned_count += $event->scanned_count ?? 0;//عدد الحضور الكلي
        }

        //التقييم(من المفضلة)
        $evaluation = Favorite::whereIn('favoritable_id', $event_ids)
            ->where('favoritable_type', Event::class)
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
            ->count();
        //__________________________________________________________
        //عدد الفعاليات الكلي
        $event_count = $events->count();
        //__________________________________________________________
        //growth Rate(في التسجيل)
        $daysDiff = max($start->diffInDays($end), 1);
        $previousStart = $start->copy()->subDays($daysDiff);
        $previousEnd = $start->copy()->subSeconds(1);

        // جلب الفعاليات للفترة السابقة لتقييم النمو
        $previous_registered = Event::where('booth_booking_id', $booking->id)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('registered_count');

        if ($previous_registered == 0)
        {
            $growth_rate = $registered_count > 0 ? 100 : 0;
        } else
        {
            $growth_rate = round((($registered_count - $previous_registered) / $previous_registered) * 100, 2);
        }
        //__________________________________________________________
        $data_out =
        [
            'booth_number' => $booth->number,
            'exhibition_name' => $booth->exhibition->name ?? 'N/A',
            'date_period' => $startDate . ' to ' . $endDate,
            'registered_count' => $registered_count,
            'growth_rate' => $growth_rate . '%',
        ];
        //__________________________________________________________
        $data_in =
        [
            'created_at' => $report->created_at->format('Y-m-d'),
            'booth_number' => $booth->number,
            'exhibition_name' => $booth->exhibition->name ?? 'N/A',
            'date_period' => $startDate . ' to ' . $endDate,

            'registered_count' => $registered_count,
            'event_count' => $event_count,
            'scanned_count' => $scanned_count,
            'evaluation' => $evaluation,
        ];
        //__________________________________________________________
        //بيانات المنحنى البياني(التسجيل عبر الفترات)

        // تجميع المسجلين حسب تاريخ الفعالية أو أوقات التسجيل
        $data_graph = $events->groupBy(function ($event)
        {
            return Carbon::parse($event->start_time ?? $event->created_at)->format('Y-m-d');
        })->map(function ($group)
        {
            return $group->sum('registered_count');
        });
        //__________________________________________________________
        //بيانات الجدول التفصيلي(اليومي)
        // event_name | registered_count | scanned_count | evaluation

        $data_specific_table = [];

        foreach ($events as $event)
        {
            // مفضلة الفعالية المحددة
            $event_favs = Favorite::where('favoritable_id', $event->id)
                ->where('favoritable_type', Event::class)
                ->count();

            $data_specific_table[] =
            [
                'event_name' => $event->title ?? $event->name,
                'registered_count' => $event->registered_count ?? 0,
                'scanned_count' => $event->scanned_count ?? 0,
                'evaluation' => $event_favs,
            ];
        }
        //__________________________________________________________
        //بناءالتوصيات تلقائياً
        $data_recommendations = [];

        //1- معدل الحضور الفعلي (Turnout Rate) مقارنة بالمتوسط
        $attendance_rate = $registered_count > 0 ? round(($scanned_count / $registered_count) * 100, 1) : 0;
        $average_sector_rate = 65; // متوسط القطاع القياسي للحضور
        if ($attendance_rate >= $average_sector_rate)
        {
            $data_recommendations[] = "معدل الحضور الفعلي للفعاليات بلغ {$attendance_rate}% وهو يتجاور متوسط القطاع البالغ {$average_sector_rate}%.";
        } else
        {
            $data_recommendations[] = "معدل الحضور الفعلي بلغ {$attendance_rate}% وهو أقل من متوسط القطاع ({$average_sector_rate}%)، يُنصح بإرسال إشعارات تذكيرية.";
        }

        //2- نمو أعداد المسجلين والاهتمام
        if ($growth_rate >= 0)
        {
            $data_recommendations[] = "تم تسجيل {$registered_count} زائراً في الفعاليات بزيادة {$growth_rate}% عن الفترة السابقة.";
        } else
        {
            $data_recommendations[] = "تم تسجيل {$registered_count} زائراً بانخفاض قدره " . abs($growth_rate) . "% عن الفترة السابقة.";
        }

        //3- معدل التحويل للمفضلة/الاهتمام (Conversion Rate)
        $conversion_rate = $scanned_count > 0 ? round(($evaluation / $scanned_count) * 100, 1) : 0;
        $general_average = 12; // المتوسط العام للتحويل للمفضلة

        if ($conversion_rate >= $general_average)
        {
            $data_recommendations[] = "معدل التحويل/الاهتمام {$conversion_rate}% أعلى من المتوسط العام {$general_average}% — نجاح ممتاز لمحتوى الفعاليات.";
        } else
        {
            $data_recommendations[] = "معدل التحويل {$conversion_rate}% يحتاج لتحسين مقارنة بالمتوسط العام {$general_average}%.";
        }

        //4- نصيحة توجيهية لتطوير الفعاليات
        if ($event_count < 2)
        {
            $data_recommendations[] = "يُنصح بتكثيف وتنويع الفعاليات داخل الجناح لرفع نسبة المشاركة واستقطاب المهتمين.";
        } else
        {
            $data_recommendations[] = "يُنصح بزيادة التفاعل أثناء الفعاليات الحالية لرفع نسبة تقييمها في المفضلة.";
        }
        //__________________________________________________________
        //DB
        $report = InvestorEventReports::updateOrCreate(
        [
            'booth_booking_id' => $booking->id, // الشرط لعدم تكرار التقرير لنفس الحجز
        ],
        [
            'investor_id' => $booking->investor_id,
            'date_period' => $startDate . ' to ' . $endDate,

            'registered_count' => $registered_count,
            'growth_rate' => $growth_rate,
            'event_count' => $event_count,
            'scanned_count' => $scanned_count,
            'evaluation' => $evaluation,

            'data_graph' => $data_graph,
            'data_specific_table' => $data_specific_table,
            'data_recommendations' => $data_recommendations,
        ]
        );
        //__________________________________________________________
    }
}
