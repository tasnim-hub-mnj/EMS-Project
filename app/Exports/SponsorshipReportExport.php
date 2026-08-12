<?php

namespace App\Exports;

use App\Models\InvestorSponsorshipsReports;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class SponsorshipReportExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $reportId;

    public function __construct(int $reportId)
    {
        $this->reportId = $reportId;
    }

    public function query()
    {
        return InvestorSponsorshipsReports::query()
            ->with(['investor'])
            ->where('id', $this->reportId);
    }

    public function headings(): array
    {
        return [
            'Company_Name',
            'Total_Campaigns',
            'Total_Reach',
            'Growth_Rate',
            'Total_Amount',
            'Total_Favorites',
            'Overall_CTR',
            'Specific_Table',
            'Recommendations',
        ];
    }

    public function map($report): array
    {
        return [
            $report->investor->company_name ?? '-',
            $report->total_campaigns ?? 0,
            $report->total_reach ?? 0,
            $report->growth_rate ?? 0,
            $report->total_amount ?? 0,
            $report->total_favorites ?? 0,
            $report->overall_ctr ?? 0,
            json_encode($report->data_specific_table, JSON_UNESCAPED_UNICODE),
            json_encode($report->data_recommendations, JSON_UNESCAPED_UNICODE),
        ];
    }
}
