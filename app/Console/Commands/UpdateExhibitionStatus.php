<?php

namespace App\Console\Commands;

use App\Models\Exhibition;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateExhibitionStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-exhibition-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the status of exhibitions based on date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $exhibitions = Exhibition::all();
        /*
        far قبل بداية المعرض بأكثر من 14 يوم.
        upcoming قبل بداية المعرض بـ 14 يوم أو أقل.
        ongoing خلال فترة المعرض (من start_date إلى end_date).
        finished بعد انتهاء المعرض مباشرة.
        hidden بعد مرور 14 يوم على انتهاء المعرض (يتم حجبه من الواجهة).
        */
        foreach( $exhibitions as $ex)
        {
            $today = now()->startOfDay();
            $startDate = Carbon::parse($ex->start_date);
            $endDate = Carbon::parse($ex->end_date);

            if ($today->lt($startDate->subDays(14)))
            {
                $ex->status = 'far';
            }
            elseif ($today->between($startDate->subDays(14), $startDate))
            {
                $ex->status = 'upcoming';
            }
             elseif ($today->between($startDate, $endDate))
            {
                $ex->status = 'ongoing';
            }
            elseif ($today->gt($endDate) && $today->lte($endDate->addDays(14)))
            {
                $ex->status = 'finished';
            }
            else
            {
                $ex->status = 'hidden';
            }

            $ex->save();
        }
    }
}
