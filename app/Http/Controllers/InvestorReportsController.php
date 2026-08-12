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
use App\Models\ReportInvestor;//
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

use function PHPSTORM_META\type;

class InvestorReportsController extends Controller
{
    public function getReports()//✅
    {
        $investor = Auth::user()->investor;

        $visitor_reports = InvestorVisitorReports::where('investor_id', $investor->id)
        ->orderBy('created_at', 'desc')
        ->get();

        $booth_reports = InvestorBoothReports::where('investor_id', $investor->id)
        ->orderBy('created_at', 'desc')
        ->get();

        $event_reports = InvestorEventReports::where('investor_id', $investor->id)
        ->orderBy('created_at', 'desc')
        ->get();

        $sponsorship_reports = InvestorSponsorshipsReports::where('investor_id', $investor->id)
        ->orderBy('created_at', 'desc')
        ->get();
        //____________Visitor_____________
        $data_out_visitor_reports = $visitor_reports->map(function ($r)
        {
            $booth = $r->boothBooking->booth;
            return
            [
                'id' => $r->id,
                'booth_number' => $booth->number,
                'exhibition_name' => $booth->exhibition->name ?? 'N/A',
                'date_period' => $r->date_period,
                'total_visitors' => $r->total_visitors,
                'growth_rate' => $r->growth_rate . '%',
            ];
        });
        //____________Booth_______________
        $data_out_booth_reports = $visitor_reports->map(function ($r)
        {
            $booth = $r->boothBooking->booth;
            return
            [
                'id' => $r->id,
                'booth_number' => $booth->number,
                'exhibition_name' => $booth->exhibition->name ?? 'N/A',
                'date_period' => $r->date_period,
                'performance_index' => $r->performance_index,
                'growth_rate' => $r->growth_rate . '%',
            ];
        });
        //____________Event_______________
        $data_out_event_reports = $visitor_reports->map(function ($r)
        {
            $booth = $r->boothBooking->booth;
            return
            [
                'id' => $r->id,
                'booth_number' => $booth->number,
                'exhibition_name' => $booth->exhibition->name ?? 'N/A',
                'date_period' => $r->date_period,
                'registered_count' => $r->registered_count,
                'growth_rate' => $r->growth_rate . '%',
            ];
        });
        //____________Sponsorship_________
        $data_out_sponsorship_reports = $visitor_reports->map(function ($r)
        {
            return
            [
                'id' => $r->id,
                'investor_name' => $investor->company_name ?? 'مستثمر',
                'total_campaigns' => $r->total_campaigns,
                'total_reach' => $r->total_reach,
                'growth_rate' => $r->growth_rate . '%',
            ];
        });
        //_________________________________
        return response()->json([
            'Visitor' => $data_out_visitor_reports,
            'Booth' => $data_out_booth_reports,
            'Event' => $data_out_event_reports,
            'Sponsorship' => $data_out_sponsorship_reports,
        ], 200);
    }
    //===============================================================
    public function getReportDetail(Request $request,$r_id)//✅
    {
        $type = $request->query('type');//[visitor,booth,event,sponsoship]
        // $request->validate([
        //     'type' => 'required|in:visitor,booth,event,sponsoship'
        // ]);
        // $type = $request->type;
        $investor = Auth::user()->investor;

        if($type == 'visitor')//+++++++++++++++++++++
        {
            $report = InvestorVisitorReports::where('investor_id', $investor->id)
            ->findOrFail($r_id);
            $booth = $report->boothBooking->booth;
            $data_in =
            [
                'id' => $report->id,
                'created_at' =>  $report->created_at->format('Y-m-d'),
                'booth_number' => $booth->number,
                'exhibition_name' => $booth->exhibition->name ?? 'N/A',
                'date_period' => $report->date_period,
                'total_visitors' => $report->total_visitors,
                'peak_hours' => $report->peak_hours,
                'average_visitors_per_hour' => $report->average_visitors_per_hour,
                'unique_visitors' => $report->unique_visitors,
            ];
            return response()->json([
                'data'=> $data_in,
                'graph'=> $report->data_graph,
                'specific_table'=> $report->data_specific_table,
                'recommendations'=> $report->data_recommendations,
            ], 200);
        }
        elseif($type == 'booth')//+++++++++++++++++++++
        {
            $report = InvestorBoothReports::where('investor_id', $investor->id)
            ->findOrFail($r_id);
            $booth = $report->boothBooking->booth;
            $data_in =
            [
                'id' => $report->id,
                'created_at' =>  $report->created_at->format('Y-m-d'),
                'booth_number' => $booth->number,
                'exhibition_name' => $booth->exhibition->name ?? 'N/A',
                'date_period' => $report->date_period,
                'performance_index' => $report->performance_index,
                'potential_clients' => $report->potential_clients,
                'events_count' => $report->events_count,
            ];
            return response()->json([
                'data'=> $data_in,
                'graph'=> $report->data_graph,
                'specific_table'=> $report->data_specific_table,
                'recommendations'=> $report->data_recommendations,
            ], 200);
        }
        elseif($type == 'event')//+++++++++++++++++++++
        {
            $report = InvestorEventReports::where('investor_id', $investor->id)
            ->findOrFail($r_id);
            $booth = $report->boothBooking->booth;
            $data_in =
            [
                'id' => $report->id,
                'created_at' =>  $report->created_at->format('Y-m-d'),
                'booth_number' => $booth->number,
                'exhibition_name' => $booth->exhibition->name ?? 'N/A',
                'date_period' => $report->date_period,
                'registered_count' => $report->registered_count,
                'events_count' => $report->events_count,
                'scanned_count' => $report->scanned_count,
                'evaluation' => $report->evaluation,
            ];
            return response()->json([
                'data'=> $data_in,
                'graph'=> $report->data_graph,
                'specific_table'=> $report->data_specific_table,
                'recommendations'=> $report->data_recommendations,
            ], 200);
        }
        else//+++++++++++++++++++++
        {
            $report = InvestorSponsorshipsReports::where('investor_id', $investor->id)
            ->findOrFail($r_id);
            $data_in =
            [
                'id' => $report->id,
                'created_at' =>  $report->created_at->format('Y-m-d'),
                'investor_name' => $investor->company_name ?? 'مستثمر',
                'total_campaigns' => $report->total_campaigns,
                'total_amount' => $report->total_amount . ' $',
                'total_reach' => $report->total_reach,
                'total_favorites' => $report->total_favorites,
                'overall_ctr' => $report->overall_ctr . '%',
            ];
            return response()->json([
                'data'=> $data_in,
                'graph'=> $report->data_graph,
                'specific_table'=> $report->data_specific_table,
                'recommendations'=> $report->data_recommendations,
            ], 200);
        }
    }
    //===============================================================
    public function downloadReport(Request $request, $r_id)
    {
        // $investor = Auth::user()->investor;
        $type = $request->query('type');//['visitor','booth','event','sponsoship']
        $format = $request->query('format');//['pdf', 'excel', 'csv']

        if ($type == 'visitor')
        {
            $report = InvestorVisitorReports::findOrFail($r_id);

            if ($format == 'excel')
            {
                return Excel::download(new VisitorReportExport($report->id), 'Visitor_Report_' . $report->id . '.xlsx');
            }
            elseif ($format == 'pdf')
            {
                return Excel::download(new VisitorReportExport($report->id), 'Visitor_Report_' . $report->id . '.pdf');
            } else
            {
                return Excel::download(new VisitorReportExport($report->id), 'Visitor_Report_' . $report->id . '.csv');
            }
        }
        elseif($type == 'booth')
        {
            $report = InvestorBoothReports::findOrFail($r_id);

            if ($format == 'excel')
            {
                return Excel::download(new BoothReportExport($report->id), 'Visitor_Report_' . $report->id . '.xlsx');
            }
            elseif ($format == 'pdf')
            {
                return Excel::download(new BoothReportExport($report->id), 'Visitor_Report_' . $report->id . '.pdf');
            } else
            {
                return Excel::download(new BoothReportExport($report->id), 'Visitor_Report_' . $report->id . '.csv');
            }
        }
        elseif($type == 'event')
        {
            $report = InvestorEventReports::findOrFail($r_id);

            if ($format == 'excel')
            {
                return Excel::download(new EventReportExport($report->id), 'Visitor_Report_' . $report->id . '.xlsx');
            }
            elseif ($format == 'pdf')
            {
                return Excel::download(new EventReportExport($report->id), 'Visitor_Report_' . $report->id . '.pdf');
            } else
            {
                return Excel::download(new EventReportExport($report->id), 'Visitor_Report_' . $report->id . '.csv');
            }

        }
        elseif($type == 'sponsoship')
        {
            $report = InvestorSponsorshipsReports::findOrFail($r_id);

            if ($format == 'excel')
            {
                return Excel::download(new SponsorshipReportExport($report->id), 'Visitor_Report_' . $report->id . '.xlsx');
            }
            elseif ($format == 'pdf')
            {
                return Excel::download(new SponsorshipReportExport($report->id), 'Visitor_Report_' . $report->id . '.pdf');
            } else
            {
                return Excel::download(new SponsorshipReportExport($report->id), 'Visitor_Report_' . $report->id . '.csv');
            }

        }
        else
        {
            return response()->json([
            'message' => 'Invalid report type.'
            ], 400);
        }
    }
    //===============================================================
    public function downloadReport2(Request $request, $r_id)
    {
        $investor = Auth::user()->investor;
        $type = $request->query('type'); // [visitor, booth, event, sponsorship]
        $format = $request->query('format'); // [excel, pdf, csv]

        $ext = match ($format)
        {
            'excel' => 'xlsx',
            'pdf'   => 'pdf',
            default => 'csv',
        };

        return match ($type)
        {
            'visitor' => Excel::download(
                new VisitorReportExport(
                    InvestorVisitorReports::where('investor_id', $investor->id)->findOrFail($r_id)->id
                ),
                "Visitor_Report_{$r_id}.{$ext}"
            ),
            'booth' => Excel::download(
                new BoothReportExport(
                    InvestorBoothReports::where('investor_id', $investor->id)->findOrFail($r_id)->id
                ),
                "Booth_Report_{$r_id}.{$ext}"
            ),
            'event' => Excel::download(
                new EventReportExport(
                    InvestorEventReports::where('investor_id', $investor->id)->findOrFail($r_id)->id
                ),
                "Event_Report_{$r_id}.{$ext}"
            ),
            'sponsorship' => Excel::download(
                new SponsorshipReportExport(
                    InvestorSponsorshipsReports::where('investor_id', $investor->id)->findOrFail($r_id)->id
                ),
                "Sponsorship_Report_{$r_id}.{$ext}"
            ),
            default => response()->json(['message' => 'Invalid report type.'], 400),
        };
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
