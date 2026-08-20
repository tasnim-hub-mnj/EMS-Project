<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\BoothBooking;
use App\Models\Exhibition;
use App\Models\StaffMember;
use App\Models\Task;
use App\Models\Ticket;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendExhibitionReportNotifications extends Command
{
    protected $signature = 'app:send-exhibition-report-notifications';

    protected $description = 'Send periodic exhibition report summaries to authorized recipients';

    public function handle(): int
    {
        Exhibition::query()->each(function (Exhibition $exhibition): void {
            $summary = [
                'visitors' => Ticket::where('exhibition_id', $exhibition->id)->count(),
                'bookings' => BoothBooking::whereHas('booth', fn ($query) => $query->where('exhibition_id', $exhibition->id))->count(),
                'staff' => StaffMember::where('exhibition_id', $exhibition->id)->count(),
                'attendance' => AttendanceRecord::where('exhibition_id', $exhibition->id)->count(),
                'tasks' => Task::where('exhibition_id', $exhibition->id)->count(),
            ];

            app(NotificationService::class)->forExhibition(
                $exhibition,
                'تقرير دوري للمعرض',
                'ملخص التقرير الدوري: ' . $summary['visitors'] . ' زائر، ' . $summary['bookings'] . ' حجز، ' . $summary['staff'] . ' موظف، ' . $summary['tasks'] . ' مهمة.',
                'report',
                'admin.reports',
                $summary,
                '/reports'
            );
        });

        return self::SUCCESS;
    }
}
