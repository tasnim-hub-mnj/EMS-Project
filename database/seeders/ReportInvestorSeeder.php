<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReportInvestor;
use App\Models\ReportMetrics;
use App\Models\Investor;
use App\Models\BoothBooking;
use Illuminate\Support\Str;

class ReportInvestorSeeder extends Seeder
{
    public function run(): void
    {
        $investors = Investor::all();
        $bookings  = BoothBooking::with('booth')->get();

        if ($investors->isEmpty()) return;

        foreach ($investors as $investor)
        {
            // سننشئ 4 تقارير لكل مستثمر
            $types = ['booth', 'event', 'visitor', 'performance'];

            foreach ($types as $type)
            {
                $booking = $bookings->random();

                $report = ReportInvestor::create([
                    'investor_id'      => $investor->id,
                    'booth_booking_id' => in_array($type, ['booth','visitor']) ? $booking->id : null,
                    'type'             => $type,
                    'period'           => '2026-Q3',
                ]);

                // إنشاء Metrics للتقرير
                $metrics = $this->generateMetrics($type);

                foreach ($metrics as $m)
                {
                    ReportMetrics::create([
                        'report_id'      => $report->id,
                        'key'            => $m['key'],
                        'label'          => $m['label'],
                        'value'          => $m['value'],
                        'trend'          => $m['trend'],
                        'sparkline_data' => json_encode($m['sparkline']),
                    ]);
                }
            }
        }
    }

    private function generateMetrics($type)
    {
        switch ($type)
        {
            case 'booth':
                return [
                    $this->metric('visitors_count', 'عدد الزوار', rand(500, 3000)),
                    $this->metric('sales_total', 'إجمالي المبيعات', rand(10000, 50000)),
                ];

            case 'event':
                return [
                    $this->metric('attendees', 'عدد الحضور', rand(50, 500)),
                    $this->metric('engagement', 'معدل التفاعل', rand(20, 90)),
                ];

            case 'visitor':
                return [
                    $this->metric('unique_visitors', 'عدد الزوار الفريدين', rand(300, 2000)),
                    $this->metric('return_rate', 'نسبة العودة', rand(10, 60)),
                ];

            case 'performance':
            default:
                return [
                    $this->metric('growth', 'نمو الأداء', rand(5, 30)),
                    $this->metric('ranking', 'ترتيب الأداء', rand(1, 10)),
                ];
        }
    }

    private function metric($key, $label, $value)
    {
        $spark = [
            rand(5, 20),
            rand(10, 30),
            rand(15, 40),
            rand(20, 50),
            rand(10, 30),
        ];

        $trend = $this->detectTrend($spark);

        return [
            'key'      => $key,
            'label'    => $label,
            'value'    => $value,
            'trend'    => $trend,
            'sparkline'=> $spark,
        ];
    }

    private function detectTrend($spark)
    {
        if ($spark[4] > $spark[0]) return 'up';
        if ($spark[4] < $spark[0]) return 'down';
        return 'stable';
    }

}
