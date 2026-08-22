<?php

namespace App\Http\Controllers;

use App\Exports\BoothReportExport;
use App\Exports\EventReportExport;
use App\Exports\SponsorshipReportExport;
use App\Exports\VisitorReportExport;
use App\Models\InvestorBoothReports;
use App\Models\InvestorEventReports;
use App\Models\InvestorSponsorshipsReports;
use App\Models\InvestorVisitorReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class InvestorReportsController extends Controller
{
    public function getReports()
    {
        $investor = Auth::user()->investor;
        $reports = collect()
            ->merge(InvestorVisitorReports::with('boothBooking.booth.exhibition')
                ->where('investor_id', $investor->id)->latest()->get()
                ->map(fn ($report) => $this->serializeReport($report, 'visitor', $investor)))
            ->merge(InvestorBoothReports::with('boothBooking.booth.exhibition')
                ->where('investor_id', $investor->id)->latest()->get()
                ->map(fn ($report) => $this->serializeReport($report, 'booth', $investor)))
            ->merge(InvestorEventReports::with('boothBooking.booth.exhibition')
                ->where('investor_id', $investor->id)->latest()->get()
                ->map(fn ($report) => $this->serializeReport($report, 'event', $investor)))
            ->merge(InvestorSponsorshipsReports::where('investor_id', $investor->id)
                ->latest()->get()
                ->map(fn ($report) => $this->serializeReport($report, 'sponsorship', $investor)))
            ->sortByDesc('created_at')->values();

        return response()->json([
            'data' => $reports,
        ], 200);
    }
    //===============================================================
    public function getReportDetail(Request $request, $r_id)
    {
        $type = $this->normalizeType($request->query('type'));
        if ($type === null) {
            return response()->json(['message' => 'A valid report type is required.'], 422);
        }
        $investor = Auth::user()->investor;
        $report = $this->findOwnedReport($type, $r_id, $investor->id);
        return response()->json([
            'data' => $this->serializeReport($report, $type, $investor),
        ], 200);
    }
    //===============================================================
    public function downloadReport(Request $request, $r_id)
    {
        $type = $this->normalizeType($request->query('type'));
        $format = $request->query('format', 'csv');
        if ($type === null || !in_array($format, ['pdf', 'excel', 'csv'], true)) {
            return response()->json(['message' => 'Invalid report type or format.'], 422);
        }

        $investor = Auth::user()->investor;
        $report = $this->findOwnedReport($type, $r_id, $investor->id);
        $filename = ucfirst($type) . '_Report_' . $report->id;

        if ($format === 'pdf') {
            return Pdf::loadHTML($this->reportPdfHtml($this->serializeReport($report, $type, $investor)))
                ->download($filename . '.pdf');
        }

        $export = match ($type) {
            'visitor' => new VisitorReportExport($report->id),
            'booth' => new BoothReportExport($report->id),
            'event' => new EventReportExport($report->id),
            'sponsorship' => new SponsorshipReportExport($report->id),
        };
        return Excel::download($export, $filename . ($format === 'excel' ? '.xlsx' : '.csv'));
    }

    private function normalizeType(?string $type): ?string
    {
        return match (strtolower(trim((string) $type))) {
            'visitor', 'visitors' => 'visitor',
            'booth', 'performance' => 'booth',
            'event', 'events' => 'event',
            'sponsorship', 'sponsorships', 'sponsoship', 'campaigns' => 'sponsorship',
            default => null,
        };
    }

    private function findOwnedReport(string $type, int|string $id, int $investorId)
    {
        $query = match ($type) {
            'visitor' => InvestorVisitorReports::with('boothBooking.booth.exhibition'),
            'booth' => InvestorBoothReports::with('boothBooking.booth.exhibition'),
            'event' => InvestorEventReports::with('boothBooking.booth.exhibition'),
            'sponsorship' => InvestorSponsorshipsReports::query(),
        };

        return $query->where('investor_id', $investorId)->findOrFail($id);
    }

    private function serializeReport($report, string $type, $investor): array
    {
        $booth = $report->boothBooking?->booth;
        $typeLabel = match ($type) {
            'visitor' => 'الزوار',
            'booth' => 'الأداء',
            'event' => 'الفعاليات',
            'sponsorship' => 'الرعايات',
        };
        $mainValue = match ($type) {
            'visitor' => (float) $report->total_visitors,
            'booth' => (float) $report->performance_index,
            'event' => (float) $report->registered_count,
            'sponsorship' => (float) $report->total_reach,
        };
        $mainLabel = match ($type) {
            'visitor' => 'إجمالي الزوار',
            'booth' => 'مؤشر الأداء',
            'event' => 'المسجلون',
            'sponsorship' => 'الوصول',
        };
        $graph = is_array($report->data_graph) ? $report->data_graph : [];

        return [
            'id' => (string) $report->id,
            'type' => match ($type) {
                'visitor' => 'visitors',
                'booth' => 'performance',
                'event' => 'events',
                'sponsorship' => 'campaigns',
            },
            'title' => "تقرير {$typeLabel}",
            'description' => "تقرير {$typeLabel} للفترة {$report->date_period}",
            'period' => (string) $report->date_period,
            'booth_name' => $booth?->number ?? '',
            'exhibition_name' => $booth?->exhibition?->name ?? '',
            'created_at' => optional($report->created_at)->format('Y-m-d'),
            'main_value' => $mainValue,
            'main_label' => $mainLabel,
            'trend' => (float) ($report->growth_rate ?? 0),
            'sparkline_data' => array_values(array_map('floatval', array_values($graph))),
            'graph' => $graph,
            'specific_table' => $report->data_specific_table ?? [],
            'recommendations' => $report->data_recommendations ?? [],
            'total_visitors' => $report->total_visitors ?? null,
            'average_visitors_per_hour' => $report->average_visitors_per_hour ?? null,
            'unique_visitors' => $report->unique_visitors ?? null,
            'peak_hours' => $report->peak_hours ?? [],
            'performance_index' => $report->performance_index ?? null,
            'potential_clients' => $report->potential_clients ?? null,
            'events_count' => $report->events_count ?? $report->event_count ?? null,
            'registered_count' => $report->registered_count ?? null,
            'scanned_count' => $report->scanned_count ?? null,
            'evaluation' => $report->evaluation ?? null,
            'investor_name' => $investor->company_name ?? 'مستثمر',
            'total_campaigns' => $report->total_campaigns ?? null,
            'total_amount' => $report->total_amount ?? null,
            'total_reach' => $report->total_reach ?? null,
            'total_favorites' => $report->total_favorites ?? null,
            'overall_ctr' => $report->overall_ctr ?? null,
        ];
    }

    private function reportPdfHtml(array $report): string
    {
        $escape = fn ($value) => htmlspecialchars(is_scalar($value)
            ? (string) $value
            : json_encode($value, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        $rows = '';
        foreach (($report['specific_table'] ?? []) as $row) {
            $cells = collect($row)->map(fn ($value) => is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE))->implode(' | ');
            $rows .= '<tr><td>' . $escape($cells) . '</td></tr>';
        }
        $recommendations = '';
        foreach (($report['recommendations'] ?? []) as $recommendation) {
            $recommendations .= '<li>' . $escape($recommendation) . '</li>';
        }
        return '<!doctype html><html dir="rtl"><meta charset="utf-8"><style>'
            . 'body{font-family:DejaVu Sans;color:#222;padding:24px}h1{color:#451952}'
            . 'table{width:100%;border-collapse:collapse;margin-top:16px}td{border:1px solid #ddd;padding:8px}'
            . '</style><h1>' . $escape($report['title']) . '</h1>'
            . '<p>' . $escape($report['description']) . '</p>'
            . '<p>القيمة الرئيسية: ' . $escape($report['main_value']) . ' ' . $escape($report['main_label']) . '</p>'
            . '<h2>البيانات التفصيلية</h2><table>' . $rows . '</table>'
            . '<h2>التوصيات</h2><ul>' . $recommendations . '</ul></html>';
    }
    //===============================================================
    // public function downloadReport(Request $request, $id)//✅
    // {
    //     $investor = Auth::user()->investor;

    //     // Validate format
    //     $format = $request->query('format');
    //     if (!in_array($format, ['pdf', 'excel', 'csv']))
    //     {
    //         return response()->json([
    //             'message' => 'Invalid format. Allowed: pdf, excel, csv'
    //         ], 400);
    //     }

    //     // Load report
    //     $report = ReportInvestor::with(['reportMetrics', 'boothBooking.booth.exhibition'])
    //         ->where('investor_id', $investor->id)
    //         ->findOrFail($id);

    //     // Build file name
    //     $fileName = "report_{$report->id}." . $format;

    //     // Convert report to array
    //     $data = $this->buildReportDataArray($report);

    //     //Generate file based on format
    //     if ($format === 'csv')
    //     {
    //         $content = $this->generateCsv($data);
    //         return response($content, 200, [
    //             'Content-Type' => 'text/csv',
    //             'Content-Disposition' => "attachment; filename={$fileName}"
    //         ]);
    //     }

    //     if ($format === 'excel')
    //     {
    //         $content = $this->generateExcel($data);
    //         return response($content, 200, [
    //             'Content-Type' => 'application/vnd.ms-excel',
    //             'Content-Disposition' => "attachment; filename={$fileName}"
    //         ]);
    //     }

    //     if ($format === 'pdf')
    //     {
    //         $content = $this->generatePdf($data);
    //         return response($content, 200, [
    //             'Content-Type' => 'application/pdf',
    //             'Content-Disposition' => "attachment; filename={$fileName}"
    //         ]);
    //     }
    // }
    //===============================================================
    // private function buildReportDataArray($report)//↕️
    // {
    //     $mainMetric = $report->reportMetrics->first();

    //     return
    //     [
    //         'id' => $report->id,
    //         'title' => $this->generateReportTitle($report),
    //         'type' => $report->type,
    //         'description' => $this->generateReportDescription($report),
    //         'period' => $report->period,
    //         'booth_name' => optional($report->boothBooking->booth)->name,
    //         'exhibition_name' => optional($report->boothBooking->booth->exhibition)->name,
    //         'created_at' => $report->created_at->format('Y-m-d'),

    //         'main_value' => $mainMetric ? (double) $mainMetric->value : 0,
    //         'main_label' => $mainMetric ? $mainMetric->label : null,
    //         'trend' => $mainMetric ? $this->convertTrend($mainMetric->trend) : null,
    //         'sparkline_data' => $mainMetric ? json_decode($mainMetric->sparkline_data, true) : [],
    //     ];
    // }
    //===============================================================
    // private function generateCsv($data)//↕️
    // {
    //     $output = fopen('php://temp', 'r+');

    //     foreach ($data as $key => $value)
    //     {
    //         if (is_array($value))
    //         {
    //             $value = implode(',', $value);
    //         }
    //         fputcsv($output, [$key, $value]);
    //     }

    //     rewind($output);
    //     return stream_get_contents($output);
    // }
    // //===============================================================
    // private function generateExcel($data)//↕️
    // {
    //     $content = "";

    //     foreach ($data as $key => $value)
    //     {
    //         if (is_array($value))
    //         {
    //             $value = implode(',', $value);
    //         }
    //         $content .= "{$key}\t{$value}\n";
    //     }

    //     return $content;
    // }
    // //===============================================================
    // private function generatePdf($data)//↕️
    // {
    //     $content = "Report\n\n";

    //     foreach ($data as $key => $value)
    //     {
    //         if (is_array($value))
    //         {
    //             $value = implode(', ', $value);
    //         }
    //         $content .= "{$key}: {$value}\n";
    //     }

    //     return $content;
    // }
    //===============================================================
    // private function generateReportTitle($report)//↕️
    // {
    //     switch ($report->type)
    //     {
    //         case 'booth':
    //             return 'Booth Report: ' . optional($report->boothBooking->booth)->name;

    //         case 'event':
    //             return 'Event Report';

    //         case 'visitor':
    //             return 'Visitor Report: ' . optional($report->boothBooking->booth)->name;

    //         case 'performance':
    //             return 'Overall Performance Report';

    //         default:
    //             return 'Report';
    //     }
    // }
    // //===============================================================
    // private function generateReportDescription($report)//↕️
    // {
    //     if ($report->reportMetrics->isEmpty())
    //     {
    //         return 'No available metrics for this report.';
    //     }

    //     $metric = $report->reportMetrics->first();

    //     return $metric->label . ': ' . $metric->value;
    // }
    // //===============================================================
    // private function convertTrend($trend)//↕️
    // {
    //     return match ($trend)
    //     {
    //         'up' => 1,
    //         'down' => -1,
    //         default => 0,
    //     };
    // }
}
