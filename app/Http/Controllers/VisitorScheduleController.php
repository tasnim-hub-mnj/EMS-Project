<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\CollectedBooths;
use Illuminate\Http\Request;

class VisitorScheduleController extends Controller
{

    public function index(Request $request)
    {
        return $request->user()
            ->collectedBooths()
            ->with('booth.exhibition')
            ->orderByDesc('scanned_at')
            ->get();
    }
    //============================================

    public function store(Request $request)
    {
        $data = $request->validate([
            'booth_id' => 'required|exists:booths,id',
            'qr_data' => 'nullable|string',
        ]);

        $booth = Booth::findOrFail($data['booth_id']);

        $collected = CollectedBooths::create([
            'user_id' => $request->user()->id,
            'booth_id' => $booth->id,
            'qr_data' => $data['qr_data'] ?? null,
            'scanned_at' => now(),
        ]);

        return response()->json($collected, 201);
    }
    //==============================================

    public function scan(Request $request)
    {
        $data = $request->validate([
            'qr_data' => 'required|string',
        ]);

        $collected = CollectedBooths::create([
            'user_id' => $request->user()->id,
            'booth_id' => Booth::where('id', $data['qr_data'])->orWhereHas('collectedBooths', function ($query) use ($data) {
                $query->where('qr_data', $data['qr_data']);
            })->value('id') ?? null,
            'qr_data' => $data['qr_data'],
            'scanned_at' => now(),
        ]);

        return response()->json($collected, 201);
    }
    //==============================================

    public function destroy(Request $request, $id)
    {
        $collected = $request->user()->collectedBooths()->findOrFail($id);
        $collected->delete();

        return response()->json(['message' => 'تمت إزالة الكشك من المجموعات']);
    }
}
