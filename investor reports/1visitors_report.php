<?php
//visitor report
//لكل جناح, يُنشأ عند بداية الحجز


use App\Models\BoothBooking;
use App\Models\CollectedBooths;
use App\Models\InvestorVisitorReports;
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
        $collected_booths = CollectedBooths::where('booth_id', $booth->id)
            ->whereBetween('scanned_at', [$start->startOfDay(), $end->endOfDay()])
            ->get();
        $visitors_count = $collected_booths->count();
        //__________________________________________________________
        //growth Rate
        $daysDiff = max($start->diffInDays($end), 1);
        $previousStart = $start->copy()->subDays($daysDiff);
        $previousEnd = $start->copy()->subSeconds(1);

        $previousCount = CollectedBooths::where('booth_id', $booth->id)
            ->whereBetween('scanned_at', [$previousStart, $previousEnd])
            ->count();

        if ($previousCount == 0)
        {
            $growth_rate = $visitors_count > 0 ? 100 : 0;
        } else
        {
            $growth_rate = round((($visitors_count - $previousCount) / $previousCount) * 100, 2);
        }
        //__________________________________________________________
        //peak time
        $hoursCount = $collected_booths->groupBy(function ($item)
        {
            return Carbon::parse($item->scanned_at)->format('H');
        })->map->count();

        $maxVisitors = $hoursCount->max() ?? 0;
        $peakHours = $hoursCount->filter(fn($count) => $count === $maxVisitors)->keys();
        //__________________________________________________________
        //Average Visitors PerHour
        $totalHours = max($start->diffInHours($end), 1);
        $average_visitors_perHour = round($visitors_count / $totalHours, 2);
        //__________________________________________________________
        //Count New Visitors and returning (unique id)
        $uniqueVisitorsCount = $collected_booths->pluck('visitor_id')->unique()->count();
        //__________________________________________________________
        $data_out =
        [
            'booth_number' => $booth->number,
            'exhibition_name' => $booth->exhibition->name ?? 'N/A',
            'date_period' => $startDate . ' to ' . $endDate,
            'total_visitors' => $visitors_count,
            'growth_rate' => $growth_rate . '%',
        ];
        //__________________________________________________________
        $data_in =
        [
            'created_at' =>  $report->created_at->format('Y-m-d'),
            'booth_number' => $booth->number,
            'exhibition_name' => $booth->exhibition->name ?? 'N/A',
            'date_period' => $startDate . ' to ' . $endDate,
            'total_visitors' => $visitors_count,
            'peak_hours' => $peakHours->toArray(),
            'average_visitors_per_hour' => $average_visitors_perHour,
            'unique_visitors' => $uniqueVisitorsCount,
        ];
        //__________________________________________________________
        //بيانات المنحنى البياني(حسب الساعات)
        $data_graph = $collected_booths->groupBy(function ($item)
        {
            return Carbon::parse($item->scanned_at)->format('Y-m-d H:00');
        })->map->count();
        //__________________________________________________________
        //بيانات الجدول التفصيلي(اليومي)
        $data_specific_table = [];

        $dailyGroups = $collected_booths->groupBy(function ($item)
        {
            return Carbon::parse($item->scanned_at)->format('Y-m-d');
        });

        $previousDayCount = null;
        foreach ($dailyGroups as $day => $records)
        {
            $dayVisitors = $records->count();

            // نسبة النمو اليومية
            $dayGrowthRate = ($previousDayCount && $previousDayCount > 0)
                ? round((($dayVisitors - $previousDayCount) / $previousDayCount) * 100, 2)
                : 0;

            // ذروة الساعة لليوم
            $dayHours = $records->groupBy(fn($i) => Carbon::parse($i->scanned_at)->format('H'))->map->count();
            $dayMax = $dayHours->max();
            $dayPeakHours = $dayHours->filter(fn($c) => $c === $dayMax)->keys();

            // معدل الإعادة اليومي (Returning Rate)
            $dayUnique = $records->pluck('visitor_id')->unique()->count();
            $repeatRate = $dayVisitors > 0 ? round((($dayVisitors - $dayUnique) / $dayVisitors) * 100, 1) : 0;

            $data_specific_table[] =
            [
                'day' => $day,
                'visitors' => $dayVisitors,
                'growth_rate' => $dayGrowthRate . '%',
                'peak_hours' => $dayPeakHours->toArray(),
                'repeat_rate' => $repeatRate . '%',
            ];

            $previousDayCount = $dayVisitors;
        }
        //__________________________________________________________
        //بناءالتوصيات تلقائياً
        $data_recommendations = [];

        // 1. التوصية الأولى: ذروة الزوار اليومية وتحديد الفترة (صباحية / مسائية)
        if ($maxVisitors > 0)
        {
            // تحديد ما إذا كانت معظم ساعات الذروة مسائية (أكبر من الساعة 12 ظهراً) أو صباحية
            $period_type = 'المسائية';
            if ($peakHours->isNotEmpty())
            {
                $firstPeakHour = (int) $peakHours->first();
                if ($firstPeakHour < 12)
                {
                    $period_type = 'الصباحية';
                }
            }

            $data_recommendations[] = "بلغت ذروة الزوار {$maxVisitors} زائراً يومياً خلال ساعات الذروة {$period_type}.";
        }

        // 2. التوصية الثانية: الزوار الجدد ونسبتهم مقارنة بالمتوسط القطاعي القياسي (25%)
        $total_scans = $collected_booths->count();
        $unique_visitors = $collected_booths->pluck('visitor_id')->unique()->count();

        $new_visitors_rate = $total_scans > 0 ? round(($unique_visitors / $total_scans) * 100, 1) : 0;
        $sector_new_visitors_average = 25; // المتوسط القطاعي للزوار الجدد

        if ($new_visitors_rate >= $sector_new_visitors_average)
        {
            $data_recommendations[] = "الزوار الجدد {$unique_visitors} ({$new_visitors_rate}%) أعلى من المتوسط القطاعي {$sector_new_visitors_average}%.";
        } else
        {
            $data_recommendations[] = "الزوار الجدد {$unique_visitors} ({$new_visitors_rate}%) أقل من المتوسط القطاعي ({$sector_new_visitors_average}%)، يُنصح بتفعيل الترويج بالجناح.";
        }

        // 3. التوصية الثالثة: الزوار العائدون (Returning Visitors) وتجربة الزيارة
        $returning_visitors = $total_scans - $unique_visitors;

        if ($returning_visitors > 0)
        {
            $data_recommendations[] = "الزوار العائدون {$returning_visitors} يدل على تجربة زيارة إيجابية ومحتوى جذاب.";
        } else
        {
            $data_recommendations[] = "لا يوجد زوار عائدون حتى الآن، يُنصح بتقديم عروض تشجيعية لإعادة زيارة الجناح.";
        }

        // 4. التوصية الرابعة: نصيحة توجيهية لرفع متوسط وقت الزيارة
        if ($average_visitors_perHour < 15)
        {
            $data_recommendations[] = "يُنصح بإثراء المحتوى التفاعلي لرفع متوسط وقت الزيارة فوق 5 دقائق.";
        } else
        {
            $data_recommendations[] = "يُنصح بزيادة عدد طاقم العمل في أوقات الذروة لتنظيم تدفق الزوار الحفاظ على جودة التجربة.";
        }
        //__________________________________________________________
        //DB
        $report = InvestorVisitorReports::updateOrCreate(
        [
            'booth_booking_id' => $booking->id, // الشرط لعدم تكرار التقرير لنفس الحجز
        ],
        [
            'investor_id' => $booking->investor_id,
            'date_period' => $startDate . ' to ' . $endDate,
            'total_visitors' => $visitors_count,
            'growth_rate' => $growth_rate,
            'peak_hours' => is_array($peakHours) ? $peakHours : $peakHours->toArray(),
            'average_visitors_per_hour' => $average_visitors_perHour,
            'unique_visitors' => $uniqueVisitorsCount,
            'data_graph' => $data_graph,
            'data_specific_table' => $data_specific_table,
            'data_recommendations' => $data_recommendations,
        ]
        );
        //__________________________________________________________
    }

}




