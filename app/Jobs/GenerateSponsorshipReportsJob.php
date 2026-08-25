<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\SponsorshipBooking;
use App\Models\SponsorEvent;
use App\Models\Favorite;
use App\Models\Investor;
use App\Models\InvestorSponsorshipsReports;
use Carbon\Carbon;

class GenerateSponsorshipReportsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ?int $investorId = null)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $investors = Investor::when($this->investorId, fn ($query) => $query->whereKey($this->investorId))
            ->whereHas('sponsorshipBookings', function ($query)
        {
            $query->where('status', 'approved');
        })->get();

        $today = Carbon::today()->format('Y-m-d');

        foreach ($investors as $investor)
        {
            //جلب كل حجوزات الرعايات المقبولة للمستثمر
            $bookings = SponsorshipBooking::where('investor_id', $investor->id)
                ->where('status', 'approved')
                ->with('sponsorEvent')
                ->get();

            if ($bookings->isNotEmpty())
            {
                //1- عدد الحملات الكلية
                $total_campaigns = $bookings->count();

                //2- المبلغ الكلي المستثمر
                $total_amount = $bookings->sum('total_price');

                //3- إجمالي الوصول الكلي (Total Reach)
                $total_reach = $bookings->sum('total_visitors');

                //4- إجمالي الحضور الفعلي
                $total_attendees = $bookings->sum('total_attendees');

                //5- إجمالي الاهتمامات (المفضلة) للفعاليات الرعاية التي اشترك بها
                $sponsor_event_ids = $bookings->pluck('sponsor_event_id')->unique();
                $total_favorites = Favorite::whereIn('favoritable_id', $sponsor_event_ids)
                    ->where('favoritable_type', SponsorEvent::class)
                    ->count();

                //6- متوسط معدل النقر/التفاعل الإجمالي (CTR)
                $overall_ctr = $total_reach > 0 ? round(($total_attendees / $total_reach) * 100, 1) : 0;
                //__________________________________________________________
                //growth Rate
                // نقيس أداء الحجوزات السابقة لنفس المستثمر (مثل الحجوزات المنتهية أو السابقة)
                $previous_reach = SponsorshipBooking::where('investor_id', $investor->id)
                    ->where('status', 'ended')
                    ->sum('total_visitors');

                if ($previous_reach == 0)
                {
                    $growth_rate = $total_reach > 0 ? 100 : 0;
                } else
                {
                    $growth_rate = round((($total_reach - $previous_reach) / $previous_reach) * 100, 1);
                }
                //__________________________________________________________
                //بيانات المنحنى البياني (الوصول اليومي التجمعي لجميع الحملات)
                $data_graph = [];
                foreach ($bookings as $booking)
                {
                    $dailyVisitors = is_array($booking->daily_visitors)
                        ? $booking->daily_visitors
                        : (json_decode($booking->daily_visitors ?? '[]', true) ?? []);

                    foreach ($dailyVisitors as $date => $visitorsCount)
                    {
                        if (!isset($data_graph[$date]))
                        {
                            $data_graph[$date] = 0;
                        }
                        $data_graph[$date] += (int) $visitorsCount;
                    }
                }
                ksort($data_graph); // ترتيب التواريخ تصاعدياً

                //__________________________________________________________
                $data_specific_table = [];

                foreach ($bookings as $booking)
                {
                    $event = $booking->sponsorEvent;

                    // حساب CTR الخاص بكل حملة
                    $campaign_reach = $booking->total_visitors ?? 0;
                    $campaign_attendees = $booking->total_attendees ?? 0;
                    $ctr = $campaign_reach > 0 ? round(($campaign_attendees / $campaign_reach) * 100, 1) : 0;
                    // تحديث صيغة الفترة
                    $duration_label = $booking->selected_duration_label
                        ?? ($booking->days . ' أيام');

                    $data_specific_table[] =
                    [
                        'campaign_name' => $event->name ?? 'حملة إعلانية',
                        'period' => $duration_label,
                        'reach' => $campaign_reach,
                        'ctr_rate' => $ctr . '%',
                        'type' => $event->type ?? 'عامة',
                    ];
                }
                //__________________________________________________________
                $data_recommendations = [];

                //1- التوصية الأولى: حجم الوصول الكلي ونسبة النمو
                $reach_formatted = $total_reach >= 1000 ? round($total_reach / 1000, 1) . 'K' : $total_reach;
                if ($growth_rate >= 0)
                {
                    $data_recommendations[] = "الحملات حققت وصولاً لـ {$reach_formatted} مستخدم بزيادة {$growth_rate}% عن السابق.";
                } else
                {
                    $data_recommendations[] = "الحملات حققت وصولاً لـ {$reach_formatted} مستخدم بانخفاض قدره " . abs($growth_rate) . "% عن السابق.";
                }

                //2- التوصية الثانية: معدل النقر/التفاعل CTR مقارنة بالمعدل الصناعي (3-5%)
                $industry_ctr_min = 3;
                $industry_ctr_max = 5;
                if ($overall_ctr > $industry_ctr_max)
                {
                    $data_recommendations[] = "معدل النقر CTR {$overall_ctr}% يتفوق على المتوسط الصناعي {$industry_ctr_min}-{$industry_ctr_max}%.";
                } else
                {
                    $data_recommendations[] = "معدل النقر CTR {$overall_ctr}% ضمن المتوسط الصناعي المستهدف ({$industry_ctr_min}-{$industry_ctr_max}%).";
                }

                //3- التوصية الثالثة: أعلى نوع حملة حققت وصولاً (Social / Physical / Exhibitions)
                $top_campaign = collect($data_specific_table)->sortByDesc('reach')->first();
                if ($top_campaign) {
                    $top_type = $top_campaign['type'];
                    $data_recommendations[] = "حملات نوع ({$top_type}) سجّلت أعلى نسبة وصول عضوي واهتمام من الزوار.";
                }

                //4- التوصية الرابعة: نصيحة توجيهية لزيادة الاستثمار
                $data_recommendations[] = "يُنصح بمضاعفة الإنفاق على القنوات والأنواع ذات أعلى معدل تحويل لزيادة العائد على الاستثمار.";
                //__________________________________________________________
                //DB
                $report = InvestorSponsorshipsReports::updateOrCreate(
                [
                    'investor_id' => $booking->investor_id,
                ],
                [
                    'total_campaigns' => $total_campaigns,
                    'total_reach' => $total_reach,
                    'growth_rate' => $growth_rate,
                    'total_amount' => $total_amount,
                    'total_favorites' => $total_favorites,
                    'overall_ctr' => $overall_ctr,

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
