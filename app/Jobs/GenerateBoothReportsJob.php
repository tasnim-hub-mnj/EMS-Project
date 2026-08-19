<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Booth;
use App\Models\BoothBooking;
use App\Models\CollectedBooths;
use App\Models\Event;
use App\Models\Favorite;
use App\Models\InvestorBoothReports;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class GenerateBoothReportsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bookings = BoothBooking::where('status', 'approved')->get();
        $today = Carbon::today()->format('Y-m-d');

        foreach ($bookings as $booking)
        {
            $start = Carbon::parse($booking->start_date);
            $end = Carbon::parse($booking->end_date);
            // $startDate = $start->format('Y-m-d');
            // $endDate = $end->format('Y-m-d');
            $startDate = $start->copy()->format('Y-m-d');
            $endDate = $end->copy()->format('Y-m-d');

            if($startDate <= $today && $endDate >= $today)
            {
                $booth = $booking->booth;
                //__________________________________________________________
                //العملاء المحتملون(Potential Clients)
                $potential_clients = Favorite::where('favoritable_id', $booth->id)
                                ->where('favoritable_type', Booth::class)
                                ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])
                                ->count();
                //__________________________________________________________
                //عدد الفعاليات(Events Count)
                $events_count = Event::where('booth_booking_id',$booking->id)->count();
                //__________________________________________________________
                //مؤشر الأداء(Performance Index - من 100)
                $collected_booths = CollectedBooths::where('booth_id', $booth->id)
                    ->whereBetween('scanned_at', [$start->startOfDay(), $end->endOfDay()])
                    ->get();

                $visitors_count = $collected_booths->count();//اجمالي الزوار
                $uniqueVisitorsCount = $collected_booths->pluck('visitor_id')->unique()->count();//العملاء الجدد

                // 1- درجة الزوار (هدف 100 زائر كحد أقصى للعلامة الكاملة 40)
                $visitor_score = min(($visitors_count / 100) * 40, 40);

                //2- درجة التحويل للمفضلة (الحد الأقصى 40)
                $fav_ratio = $visitors_count > 0 ? ($potential_clients / $visitors_count) : 0;
                $favorite_score = min($fav_ratio * 40, 40);

                //3- درجة الفعاليات (كل فعالية 10 نقاط - أقصى حد 20)
                $event_score = min($events_count * 10, 20);

                $performance_index = round($visitor_score + $favorite_score + $event_score, 1);
                //__________________________________________________________
                //معدل النمو في مؤشر الاداء(growth_rate)
                $daysDiff = max($start->diffInDays($end), 1);
                $previousStart = $start->copy()->subDays($daysDiff);
                $previousEnd = $start->copy()->subSeconds(1);

                $prev_visitors = CollectedBooths::where('booth_id', $booth->id)
                    ->whereBetween('scanned_at', [$previousStart, $previousEnd])
                    ->count();

                $prev_favs = Favorite::where('favoritable_id', $booth->id)
                    ->where('favoritable_type', Booth::class)
                    ->whereBetween('created_at', [$previousStart, $previousEnd])
                    ->count();

                $prev_visitor_score = min(($prev_visitors / 100) * 40, 40);
                $prev_fav_ratio = $prev_visitors > 0 ? ($prev_favs / $prev_visitors) : 0;
                $prev_favorite_score = min($prev_fav_ratio * 40, 40);
                $prev_performance_index = round($prev_visitor_score + $prev_favorite_score + $event_score, 1);

                if ($prev_performance_index == 0)
                {
                    $growth_rate = $performance_index > 0 ? 100 : 0;
                } else
                {
                    $growth_rate = round((($performance_index - $prev_performance_index) / $prev_performance_index) * 100, 2);
                }
                //__________________________________________________________
                //بيانات المنحنى البياني(مؤشر الاداء)
                $data_graph = [];
                $period = CarbonPeriod::create($startDate, $endDate);

                foreach ($period as $date)
                {
                    $dayStr = $date->format('Y-m-d');

                    $dayVisitors = CollectedBooths::where('booth_id', $booth->id)
                        ->whereDate('scanned_at', $dayStr)
                        ->count();

                    $dayFavs = Favorite::where('favoritable_id', $booth->id)
                        ->where('favoritable_type', Booth::class)
                        ->whereDate('created_at', $dayStr)
                        ->count();

                    $dayVScore = min(($dayVisitors / 20) * 40, 40); // هدف يومي 20 زائر
                    $dayFRatio = $dayVisitors > 0 ? ($dayFavs / $dayVisitors) : 0;
                    $dayFScore = min($dayFRatio * 40, 40);

                    $data_graph[$dayStr] = round($dayVScore + $dayFScore + $event_score, 1);
                }
                //__________________________________________________________
                //بيانات الجدول التفصيلي(اليومي)
                //day | performance_index | potential_clients | events_count

                $data_specific_table = [];

                foreach ($period as $date)
                {
                    $dayStr = $date->format('Y-m-d');

                    $dayVisitors = CollectedBooths::where('booth_id', $booth->id)
                        ->whereDate('scanned_at', $dayStr)
                        ->count();

                    $dayFavs = Favorite::where('favoritable_id', $booth->id)
                        ->where('favoritable_type', Booth::class)
                        ->whereDate('created_at', $dayStr)
                        ->count();

                    $dayEvents = Event::where('booth_booking_id', $booking->id)
                        ->whereDate('created_at', $dayStr)
                        ->count();

                    $data_specific_table[] =
                    [
                        'day' => $dayStr,
                        'performance_index' => $data_graph[$dayStr] ?? 0,
                        'potential_clients' => $dayFavs,
                        'events_count' => $dayEvents,
                    ];
                }
                //__________________________________________________________
                //بناءالتوصيات تلقائياً
                $data_recommendations = [];

                //1- التوصية الأولى: المقارنة بمتوسط القطاع (الافتراضي 72/100)
                $sector_average = 72;
                if ($performance_index >= $sector_average)
                {
                    $data_recommendations[] = "مؤشر الأداء {$performance_index}/100 يتجاوز متوسط القطاع البالغ {$sector_average}/100.";
                } else
                {
                    $data_recommendations[] = "مؤشر الأداء {$performance_index}/100 أقل من متوسط القطاع ({$sector_average}/100)، يُنصح بتكثيف الفعاليات.";
                }

                //2- التوصية الثانية: عدد العملاء المحتملين ونسبتهم
                if ($growth_rate >= 0)
                {
                    $data_recommendations[] = "تم توليد {$potential_clients} عميلاً محتملاً بزيادة {$growth_rate}% عن الفترة السابقة.";
                } else
                {
                    $data_recommendations[] = "تم توليد {$potential_clients} عميلاً محتملاً مع انخفاض بنسبة " . abs($growth_rate) . "% عن الفترة السابقة.";
                }

                //3- التوصية الثالثة: نصيحة توجيهية بناءً على الأرقام
                if ($events_count == 0)
                {
                    $data_recommendations[] = "يُنصح بجدولة فعاليات داخل الجناح لجذب المزيد من الزوار لرفع مؤشر الأداء.";
                } else
                {
                    $data_recommendations[] = "يُنصح بزيادة عدد ممثلي الجناح خلال أيام الذروة لرفع الكفاءة واستقطاب المهتمين.";
                }
                //__________________________________________________________
                //DB
                $report = InvestorBoothReports::updateOrCreate(
                [
                    'booth_booking_id' => $booking->id, // الشرط لعدم تكرار التقرير لنفس الحجز
                ],
                [
                    'investor_id' => $booking->investor_id,
                    'date_period' => $startDate . ' to ' . $endDate,
                    'performance_index' => $performance_index,
                    'growth_rate' => $growth_rate,
                    'potential_clients' => $potential_clients,
                    'events_count' => $events_count,

                    'data_graph' => $data_graph,
                    'data_specific_table' => $data_specific_table,
                    'data_recommendations' => $data_recommendations,
                ]
                );
                //__________________________________________________________
            }
        }
    }
}
