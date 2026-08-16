<?php

namespace App\Http\Controllers;

use App\Models\StaffSalary;
use App\Http\Resources\PayrollSummaryResource;
use App\Http\Resources\PayrollEntryResource;

class PayrollController extends Controller
{
    //?month=YYYY-MM&team=optional
    public function summary()
    {
        $month = request('month'); // مثال: 2026-08
        $team = request('team');   // optional

        if (!$month) 
        {
            return response()->json(['message' => 'month is required'], 422);
        }

        // فصل السنة عن الشهر
        [$year, $monthNumber] = explode('-', $month);

        $query = StaffSalary::with('staff')
            ->where('year', $year)
            ->where('month', $monthNumber);

        if ($team && $team !== 'all') 
        {
            $query->where('type_staff', $team);
        }

        $entries = $query->get();

        // حساب المجاميع
        $totalGross = $entries->sum('gross');
        $totalDeductions = $entries->sum('deductions');
        $totalNet = $entries->sum('net');

        return new PayrollSummaryResource([
            'team' => $team ?? 'all',
            'month' => $month,
            'totalGross' => $totalGross,
            'totalDeductions' => $totalDeductions,
            'totalNet' => $totalNet,
            'entries' => $entries,
        ]);
    }
    //================================================================
    //?month=YYYY-MM&team=optional
    public function entries()
    {
        $month = request('month');
        $team = request('team');

        if (!$month) 
        {
            return response()->json(['message' => 'month is required'], 422);
        }

        [$year, $monthNumber] = explode('-', $month);

        $query = StaffSalary::with('staff')
            ->where('year', $year)
            ->where('month', $monthNumber);

        if ($team && $team !== 'all') 
        {
            $query->where('type_staff', $team);
        }

        return PayrollEntryResource::collection($query->get());
    }
    //================================================================
}
