<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateEventStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-event-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $events = Event::all();

        foreach ($events as $event)
        {
            $now = now();
            $today = now()->startOfDay();

            $startDate = Carbon::parse($event->start_date);
            $endDate   = Carbon::parse($event->end_date);

            // دمج التاريخ + الوقت
            $eventStartDateTime = Carbon::parse($event->start_date . ' ' . $event->time);

            /*
                upcoming  قبل بداية الوقت
                ongoing   خلال الحدث (بعد بداية الوقت)
                finished  بعد نهاية التاريخ
            */

            // 1) إذا اليوم قبل تاريخ البداية → قادمة
            if ($today->lt($startDate))
            {
                $event->status = 'upcoming';
            }

            // 2) إذا اليوم هو يوم البداية لكن الوقت لسا ما إجا → قادمة
            elseif ($today->equalTo($startDate) && $now->lt($eventStartDateTime))
            {
                $event->status = 'upcoming';
            }

            // 3) إذا الوقت إجا أو مرّ، وكان اليوم ضمن فترة الحدث → جارية
            elseif ($now->greaterThanOrEqualTo($eventStartDateTime) && $today->between($startDate, $endDate))
            {
                $event->status = 'ongoing';
            }

            // 4) إذا اليوم بعد تاريخ النهاية → منتهية
            elseif ($today->gt($endDate))
            {
                $event->status = 'finished';
            }

            $event->save();
        }
    }
}
