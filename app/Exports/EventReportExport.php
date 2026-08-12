<?php

namespace App\Exports;

use App\Models\InvestorEventReports;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class EventReportExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $reportId;

    public function __construct(int $reportId)
    {
        $this->reportId = $reportId;
    }

    public function query()
    {
        return InvestorEventReports::query()
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
            'Registered_Count',
            'Growth_Rate',
            'Event_Count',
            'Scanned_Count',
            'Evaluation',
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
            $report->registered_count ?? 0,
            $report->growth_rate ?? 0,
            $report->event_count ?? 0,
            $report->scanned_count ?? 0,
            $report->evaluation ?? 0,
            json_encode($report->data_specific_table, JSON_UNESCAPED_UNICODE),
            json_encode($report->data_recommendations, JSON_UNESCAPED_UNICODE),
        ];
    }
}
