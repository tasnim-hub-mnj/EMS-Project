<?php

namespace App\Console\Commands;

use App\Models\SponsorEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateSponsorEventStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-sponsor-event-status';

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
        $sponsor_events = SponsorEvent::all();
        /*
        upcoming قبل البداية
        ongoing خلال الحدث
        finished بعد الانتهاء مباشرة
        */
        foreach( $sponsor_events as $event)
        {
            $today = now()->startOfDay();
            $startDate = Carbon::parse($event->start_date);
            $endDate = Carbon::parse($event->end_date);

            if ($today->lt($startDate))
            {
                $event->status = 'upcoming';
            }
            elseif ($today->between($startDate, $endDate))
            {
                $event->status = 'ongoing';
            }
            elseif ($today->gt($endDate))
            {
                $event->status = 'finished';
            }

            $event->save();
        }
    }
}
