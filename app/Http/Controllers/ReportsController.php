<?php

namespace App\Http\Controllers;

use App\Models\ReportInvestor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    public function getReports()//✅
    {
        $investor = Auth::user()->investor;

        $reports = ReportInvestor::with([
            'reportMetrics',
            'boothBooking.booth.exhibition'
        ])
        ->where('investor_id', $investor->id)
        ->orderBy('created_at', 'desc')
        ->get();

        $data = $reports->map(function ($r)
        {
            $mainMetric = $r->reportMetrics->first();
            return
            [
                'id' => (string) $r->id,
                'title' => $this->generateReportTitle($r),
                'type' => $r->type,
                'description' => $this->generateReportDescription($r),
                'period' => $r->period,

                'booth_name' => optional($r->boothBooking->booth)->name,
                'exhibition_name' => optional($r->boothBooking->booth->exhibition)->name,

                'created_at' => $r->created_at->format('Y-m-d'),

                'main_value' => $mainMetric ? (double) $mainMetric->value : 0,
                'main_label' => $mainMetric ? $mainMetric->label : null,
                'trend' => $mainMetric ? $this->convertTrend($mainMetric->trend) : null,
                'sparkline_data' => $mainMetric ? json_decode($mainMetric->sparkline_data, true) : [],
            ];
        });

        return response()->json([
            'data' => $data
        ], 200);
    }
    //===============================================================
    public function getReportDetail($r_id)//✅
    {
        $investor = Auth::user()->investor;

        $r = ReportInvestor::with([
            'metrics',
            'boothBooking.booth.exhibition'
        ])
        ->where('investor_id', $investor->id)
        ->findOrFail($r_id);

        $mainMetric = $r->reportMetrics->first();

        return response()->json([
            'data' =>
            [
                'id' => (string) $r->id,
                'title' => $this->generateReportTitle($r),
                'type' => $r->type,
                'description' => $this->generateReportDescription($r),
                'period' => $r->period,

                'booth_name' => optional($r->boothBooking->booth)->name,
                'exhibition_name' => optional($r->boothBooking->booth->exhibition)->name,

                'created_at' => $r->created_at->format('Y-m-d'),

                'main_value' => $mainMetric ? (double) $mainMetric->value : 0,
                'main_label' => $mainMetric ? $mainMetric->label : null,
                'trend' => $mainMetric ? $this->convertTrend($mainMetric->trend) : null,
                'sparkline_data' => $mainMetric ? json_decode($mainMetric->sparkline_data, true) : [],
            ]
        ], 200);
    }
    //===============================================================
    private function generateReportTitle($report)//↕️
    {
        switch ($report->type)
        {
            case 'booth':
                return 'Booth Report: ' . optional($report->boothBooking->booth)->name;

            case 'event':
                return 'Event Report';

            case 'visitor':
                return 'Visitor Report: ' . optional($report->boothBooking->booth)->name;

            case 'performance':
                return 'Overall Performance Report';

            default:
                return 'Report';
        }
    }
    //===============================================================
    private function generateReportDescription($report)//↕️
    {
        if ($report->reportMetrics->isEmpty())
        {
            return 'No available metrics for this report.';
        }

        $metric = $report->reportMetrics->first();

        return $metric->label . ': ' . $metric->value;
    }
    //===============================================================
    private function convertTrend($trend)//↕️
    {
        return match ($trend)
        {
            'up' => 1,
            'down' => -1,
            default => 0,
        };
    }
    //===============================================================
    public function downloadReport(Request $request, $id)//✅
    {
        $investor = Auth::user()->investor;

        // Validate format
        $format = $request->query('format');
        if (!in_array($format, ['pdf', 'excel', 'csv']))
        {
            return response()->json([
                'message' => 'Invalid format. Allowed: pdf, excel, csv'
            ], 400);
        }

        // Load report
        $report = ReportInvestor::with(['reportMetrics', 'boothBooking.booth.exhibition'])
            ->where('investor_id', $investor->id)
            ->findOrFail($id);

        // Build file name
        $fileName = "report_{$report->id}." . $format;

        // Convert report to array
        $data = $this->buildReportDataArray($report);

        //Generate file based on format
        if ($format === 'csv')
        {
            $content = $this->generateCsv($data);
            return response($content, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename={$fileName}"
            ]);
        }

        if ($format === 'excel')
        {
            $content = $this->generateExcel($data);
            return response($content, 200, [
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => "attachment; filename={$fileName}"
            ]);
        }

        if ($format === 'pdf')
        {
            $content = $this->generatePdf($data);
            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename={$fileName}"
            ]);
        }
    }
    //===============================================================
    private function buildReportDataArray($report)//↕️
    {
        $mainMetric = $report->reportMetrics->first();

        return
        [
            'id' => $report->id,
            'title' => $this->generateReportTitle($report),
            'type' => $report->type,
            'description' => $this->generateReportDescription($report),
            'period' => $report->period,
            'booth_name' => optional($report->boothBooking->booth)->name,
            'exhibition_name' => optional($report->boothBooking->booth->exhibition)->name,
            'created_at' => $report->created_at->format('Y-m-d'),

            'main_value' => $mainMetric ? (double) $mainMetric->value : 0,
            'main_label' => $mainMetric ? $mainMetric->label : null,
            'trend' => $mainMetric ? $this->convertTrend($mainMetric->trend) : null,
            'sparkline_data' => $mainMetric ? json_decode($mainMetric->sparkline_data, true) : [],
        ];
    }
    //===============================================================
    private function generateCsv($data)//↕️
    {
        $output = fopen('php://temp', 'r+');

        foreach ($data as $key => $value)
        {
            if (is_array($value))
            {
                $value = implode(',', $value);
            }
            fputcsv($output, [$key, $value]);
        }

        rewind($output);
        return stream_get_contents($output);
    }
    //===============================================================
    private function generateExcel($data)//↕️
    {
        $content = "";

        foreach ($data as $key => $value)
        {
            if (is_array($value))
            {
                $value = implode(',', $value);
            }
            $content .= "{$key}\t{$value}\n";
        }

        return $content;
    }
    //===============================================================
    private function generatePdf($data)//↕️
    {
        $content = "Report\n\n";

        foreach ($data as $key => $value)
        {
            if (is_array($value))
            {
                $value = implode(', ', $value);
            }
            $content .= "{$key}: {$value}\n";
        }

        return $content;
    }
    //===============================================================
}
