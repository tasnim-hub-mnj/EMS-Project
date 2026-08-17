<?php

namespace App\Jobs;

use App\Models\Booth;
use App\Models\BoothBooking;
use App\Models\Copy;
use App\Models\Exhibition;
use App\Models\Favorite;
use App\Models\Investor;
use App\Models\PortalLink;
use App\Models\SponserEventTicket;
use App\Models\SponsorEvent;
use App\Models\SponsorshipBooking;
use App\Models\SponsorshipRequest;
use App\Models\StaffRole;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class GenerateExhibitionCopiesJob implements ShouldQueue
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
        $exhibitions = Exhibition::with(['booths', 'sponsors'])->get();
        $today = Carbon::today();

        foreach ($exhibitions as $exhibition) 
        {
            $exhibition_id = $exhibition->id;

            $start = Carbon::parse($exhibition->start_date)->startOfDay();
            $end = Carbon::parse($exhibition->end_date)->endOfDay();
            $year = $start->format('Y');

            // 1. تحديد حالة النسخة بناءً على التاريخ الحالي
            if ($today->between($start, $end)) 
            {
                $copy_status = 'active';
            } 
            elseif ($today->greaterThan($end)) 
            {
                $copy_status = 'finished';
            } 
            else 
            {
                $copy_status = 'archived'; // المعرض لم يبدأ بعد
            }

            // جلب النسخة الحالية إن كانت موجودة سابقاً
            $copy = Copy::where('exhibition_id', $exhibition_id)
                ->where('year', $year)
                ->first();

            // ==========================================

            //1- إذا كانت أرشفة (لم تبدأ بعد) -> تتجاوز ولا تفعل شيئاً
            if ($copy_status === 'archived') 
            {
                continue;
            }

            // 2-إذا كانت منتهية (Finished) وسبق أن تم تحديثها بعد تاريخ انتهاء المعرض -> تتجاوز
            if ($copy_status === 'finished' && $copy) 
            {
                // إذا تم التحديث بتاريخ يساوي أو بعد تاريخ نهاية المعرض، فهذا يعني أن اللقطة الأخيرة أخذت بالفعل
                if ($copy->updated_at->greaterThanOrEqualTo($end)) 
                {
                    continue;
                }
            }

            // 3- إذا كانت فعالة (Active) -> ستكمل وتنفيذ التحديث دائماً!

            // ==========================================
            //announced
            $announced = ($copy_status === 'active');
            //=====================================
            // pending_requests
            $booth_ids = $exhibition->booths->pluck('id');
            $pending_requests = BoothBooking::whereIn('booth_id', $booth_ids)
                ->where('status', 'pending')
                ->count();
            //=====================================
            // expected_visitors
            // تحسين الأداء: التصفية عبر الاستعلام أو معالجة تحسين الذاكرة
            $sectors = $exhibition->sectors ?? [];
            $interested_visitors = 0;

            if (!empty($sectors)) {
                $interested_visitors = Visitor::where(function($q) use ($sectors) {
                    foreach ($sectors as $sector) {
                        $q->orWhereJsonContains('interests', $sector);
                    }
                })->count();
            }

            $ticket_buyers = Ticket::where('exhibition_id', $exhibition_id)
                ->pluck('user_id')
                ->unique()
                ->count();

            $previous_expected = Copy::where('exhibition_id', $exhibition_id)
                ->where('year', '<', $year)
                ->avg('expected_visitors');

            $expected_visitors =
                ($interested_visitors * 0.6) +
                ($ticket_buyers * 0.3) +
                (($previous_expected ?? 0) * 0.1);
            //=====================================
            // turnout_percent
            $interested_investors = Investor::where('activity_type', $exhibition->type)->count();
            $actual_visitors = $exhibition->visitors_count ?? 0;
            $base_turnout = $interested_visitors + $interested_investors;
            $turnout_percent = $base_turnout > 0
                ? ($actual_visitors / $base_turnout) * 100
                : 0;
            //=====================================
            // expected_turnout_percent (من النسخ السابقة)
            $previous_copies = Copy::where('exhibition_id', $exhibition_id)
                ->where('year', '<', $year)
                ->with('exhibition')
                ->get();

            $turnout_values = [];

            foreach ($previous_copies as $prev) 
            {
                $prev_exhibition = $prev->exhibition;
                if (!$prev_exhibition) continue;

                $prev_sectors = $prev_exhibition->sectors ?? [];
                $prev_interested_visitors = 0;

                if (!empty($prev_sectors)) {
                    $prev_interested_visitors = Visitor::where(function($q) use ($prev_sectors) {
                        foreach ($prev_sectors as $s) {
                            $q->orWhereJsonContains('interests', $s);
                        }
                    })->count();
                }

                $prev_interested_investors = Investor::where('activity_type', $prev_exhibition->type)->count();
                $prev_actual_visitors = $prev_exhibition->visitors_count ?? 0;

                $base = $prev_interested_visitors + $prev_interested_investors;

                $prev_turnout = $base > 0
                    ? ($prev_actual_visitors / $base) * 100
                    : 0;

                $turnout_values[] = $prev_turnout;
            }

            $expected_turnout_percent = count($turnout_values) > 0
                ? array_sum($turnout_values) / count($turnout_values)
                : 0;
            //=====================================
            // revenue
            $booth_bokings_revenue = BoothBooking::where('exhibition_id', $exhibition_id)
                ->whereIn('status', ['approved', 'finished'])
                ->sum('total_price');

            $tickets_revenue = Ticket::where('exhibition_id', $exhibition_id)->sum('amount');
            $sponsorships_bookings_revenue = SponsorshipBooking::where('exhibition_id', $exhibition_id)->sum('total_price');

            $sponsor_events_ids = SponsorEvent::where('exhibition_id', $exhibition_id)->pluck('id');
            $sponsorEvent_tickets_revenue = SponserEventTicket::whereIn('sponsor_event_id', $sponsor_events_ids)->sum('amount');

            $revenue = $booth_bokings_revenue +
                $tickets_revenue +
                $sponsorships_bookings_revenue +
                $sponsorEvent_tickets_revenue;
            //=====================================
            // expected_revenue
            $min_booth_price = Booth::where('exhibition_id', $exhibition_id)->min('price') ?? 0;

            $investors_ids = User::where('role', 'investor')->pluck('id');
            $expected_investors = Favorite::where('favoritable_id', $exhibition_id)
                ->where('favoritable_type', Exhibition::class)
                ->whereIn('user_id', $investors_ids)
                ->count();

            $expected_revenue = ($tickets_revenue * $expected_visitors)
                + ($min_booth_price * $expected_investors)
                + $revenue;
            //=====================================
            // staff_count
            $staff_count = PortalLink::where('exhibition_id', $exhibition_id)->count();
            //=====================================
            // sponsorship_percent
            $sponsors_ids = $exhibition->sponsors->pluck('id');

            $total_requests = SponsorshipRequest::whereIn('sponsor_id', $sponsors_ids)->count();
            $approved_requests = SponsorshipRequest::whereIn('sponsor_id', $sponsors_ids)
                ->where('status', 'approved')
                ->count();

            $sponsorship_percent = $total_requests > 0
                ? round(($approved_requests / $total_requests) * 100, 2)
                : 0;
            //=====================================
            // final_booked_booths
            $final_booked_booths = BoothBooking::whereIn('booth_id', $booth_ids)
                ->where('status', 'finished')
                ->count();
            //=====================================
            /*
            $table->integer('total_booths')->default(0);//exhibition->total_booths
            $table->integer('booked_booths')->default(0);//total_booths - available_booths
            $table->integer('available_booths')->default(0);//exhibition->available_booths
            */
            //=====================================
            // إنشاء أو تحديث البيانات
            Copy::updateOrCreate(
                [
                    'exhibition_id' => $exhibition->id,
                    'year' => $year
                ],
                [
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d'),
                    'copy_status' => $copy_status,
                    'announced' => $announced,
                    '$total_booths' => $exhibition->total_booths,
                    '$booked_booths' => $exhibition->total_booths - $exhibition->available_booths,
                    '$available_booths' => $exhibition->available_booths,
                    'pending_requests' => $pending_requests,
                    'visitor_count' => $exhibition->visitors_count,
                    'expected_visitors' => $expected_visitors,
                    'turnout_percent' => $turnout_percent,
                    'expected_turnout_percent' => $expected_turnout_percent,
                    'revenue' => $revenue,
                    'expected_revenue' => $expected_revenue,
                    'staff_count' => $staff_count,
                    'sponsorship_percent' => $sponsorship_percent,
                    'final_booked_booths' => $final_booked_booths,
                ]
            );
            
        }
    }

}
