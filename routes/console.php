<?php

use App\Console\Commands\UpdateBookingStatus;
use App\Console\Commands\UpdateEventStatus;
use App\Console\Commands\UpdateExhibitionStatus;
use App\Console\Commands\UpdateSponsorEventStatus;

use App\Jobs\GenerateBoothReportsJob;
use App\Jobs\GenerateExhibitionCopiesJob;
use App\Jobs\GenerateEventReportsJob;
use App\Jobs\GenerateSponsorshipReportsJob;
use App\Jobs\GenerateVisitorReportsJob;

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(UpdateExhibitionStatus::class)->dailyAt('00:00');
Schedule::command(UpdateEventStatus::class)->dailyAt('00:00');
Schedule::command(UpdateSponsorEventStatus::class)->dailyAt('00:00');
Schedule::command(UpdateBookingStatus::class)->dailyAt('00:00');

Schedule::job(new GenerateVisitorReportsJob)->everyTwoHours();
Schedule::job(new GenerateBoothReportsJob)->everyTwoHours();
Schedule::job(new GenerateEventReportsJob)->everyTwoHours();
Schedule::job(new GenerateSponsorshipReportsJob)->everyTwoHours();

Schedule::job(new GenerateExhibitionCopiesJob)->everyTwoHours();

// Schedule::job(new GenerateExhibitionCopiesJob)->yearlyOn();



