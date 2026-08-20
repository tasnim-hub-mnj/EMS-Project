<?php

namespace App\Http\Controllers;

use App\Models\StaffSalary;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\PortalLink;
use Illuminate\Support\Collection;
use App\Http\Resources\PayrollSummaryResource;
use App\Http\Resources\PayrollEntryResource;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    private function entriesForMonth(string $year, string $monthNumber, ?string $team): Collection
    {
        $user = Auth::user();
        $exhibitionId = ($user instanceof User
            ? $user->organizer()->first()?->exhibition()->first()?->id
            : null)
            ?? PortalLink::query()
                ->where('token', request()->header('X-Portal-Token'))
                ->where('active', true)
                ->value('exhibition_id');
        $query = StaffSalary::with('staff')
            ->whereHas('staff', fn ($q) => $q->where('exhibition_id', $exhibitionId))
            ->where('year', $year)
            ->where('month', $monthNumber);

        if ($team && $team !== 'all') {
            $storedTeam = $team === 'service' ? 'services' : $team;
            $query->where('type_staff', $storedTeam);
        }

        $entries = $query->get();
        if ($entries->isNotEmpty()) {
            return $entries;
        }

        $staffQuery = StaffMember::query()
            ->where('exhibition_id', $exhibitionId)
            ->where('salary', '>', 0);
        if ($team && $team !== 'all') {
            $storedTeam = $team === 'service' ? 'services' : $team;
            $staffQuery->where('team', $storedTeam);
        }

        return $staffQuery->get()->map(fn (StaffMember $staff) => [
            'id' => 'base-' . $staff->id,
            'staffId' => $staff->number,
            'staffName' => $staff->name,
            'team' => $staff->team,
            'month' => $year . '-' . str_pad($monthNumber, 2, '0', STR_PAD_LEFT),
            'gross' => (float) $staff->salary,
            'deductions' => 0,
            'net' => (float) $staff->salary,
            'notes' => 'الراتب الأساسي',
        ]);
    }

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

        $entries = $this->entriesForMonth($year, $monthNumber, $team);

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

        return PayrollEntryResource::collection($this->entriesForMonth($year, $monthNumber, $team));
    }
    //================================================================
}
