<?php

namespace App\Console\Commands;

use App\Models\BoothBooking;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class UpdateBookingStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-booking-status';

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
        $bookings = BoothBooking::all();
        /*
        pending قبل البداية و يكون تلقائي
        finished بعد الانتهاء مباشرة
        */

        foreach( $bookings as $bo)
        {
            $today = now()->startOfDay();
            $startDate = Carbon::parse($bo->start_date);
            $endDate = Carbon::parse($bo->end_date);

            if ($today->gt($endDate))
            {
                $bo->status = 'finished';
                $bo->booth->status_inv = 'available';
                $bo->booth->save();
            }
            elseif ($today->lt($startDate))
            {
                $bo->status = 'pending';
            }

            $bo->save();
        }
    }
}
