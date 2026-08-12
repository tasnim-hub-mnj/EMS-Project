<?php

namespace App\Exports;

use App\Models\InvestorBoothReports;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class BoothReportExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $reportId;

    public function __construct(int $reportId)
    {
        $this->reportId = $reportId;
    }

    public function query()
    {
        return InvestorBoothReports::query()
            ->with(['investor', 'boothBooking.booth.exhibition'])
            ->where('id', $this->reportId);
    }

    public function headings(): array
    {
        return [
            'Company_Name',
            'Exhibition_Name',
            'Booth_Number',
            'Date_Period',
            'Performance_Index',
            'Growth_Rate',
            'Potential_Clients',
            'Events_Count',
            'Specific_Table',
            'Recommendations',
        ];
    }

    public function map($report): array
    {
        return [
            $report->investor->company_name ?? '-',
            $report->boothBooking->booth->exhibition->name ?? '-',
            $report->boothBooking->booth->number ?? '-',
            $report->date_period ?? '-',
            $report->performance_index ?? 0,
            $report->growth_rate ?? 0,
            $report->potential_clients ?? 0,
            $report->events_count ?? 0,
            json_encode($report->data_specific_table, JSON_UNESCAPED_UNICODE),
            json_encode($report->data_recommendations, JSON_UNESCAPED_UNICODE),
        ];
    }
}
