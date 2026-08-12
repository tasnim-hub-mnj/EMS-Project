<?php

namespace App\Exports;

use App\Models\InvestorVisitorReports;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;


class VisitorReportExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $reportId;

    // استقبال رقم التقرير من الكونترولر
    public function __construct(int $reportId)
    {
        $this->reportId = $reportId;
    }

    // جلب التقرير المحدد فقط
    public function query()
    {
        return InvestorVisitorReports::query()
            ->with(['investor', 'boothBooking.booth.exhibition']) // Eager loading
            ->where('id', $this->reportId);
    }

    public function headings(): array
    {
        return [
            'Company_Name',
            'Exhibition_Name',
            'Booth_Number',
            'Date_Period',
            'Total_Visitors',
            'Growth_Rate',
            'Peak_Hours',
            'Average_Visitors_Per_Hour',
            'Unique_Visitors',
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
            $report->total_visitors ?? 0,
            $report->growth_rate ?? 0,
            is_array($report->peak_hours) ? json_encode($report->peak_hours, JSON_UNESCAPED_UNICODE) : ($report->peak_hours ?? '-'),
            $report->average_visitors_per_hour ?? 0,
            $report->unique_visitors ?? 0,
            is_array($report->data_specific_table) ? json_encode($report->data_specific_table, JSON_UNESCAPED_UNICODE) : ($report->data_specific_table ?? '-'),
            is_array($report->data_recommendations) ? json_encode($report->data_recommendations, JSON_UNESCAPED_UNICODE) : ($report->data_recommendations ?? '-'),
        ];
    }

}
