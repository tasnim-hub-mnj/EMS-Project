<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\CollectedBooths;
use Illuminate\Http\Request;

class CollectedBoothController extends Controller
{
    //عرض كل الاجنحة المحفوظة
    public function index(Request $request)
    {
        return $request->user()
            ->collectedBooths()
            ->with('booth.exhibition')
            ->orderByDesc('scanned_at')
            ->get();
    }
    //==============================================
    //حفظ جناح في المجموعة
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
    //================================================
    //مسح qr الجناح

    public function scan(Request $request)
    {
        $data = $request->validate([
            'qr_data' => 'required|string',
        ]);

        $exists = CollectedBooths::where('user_id', $request->user()->id)
            ->where('qr_data', $data['qr_data'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'تم مسح هذا الكود مسبقاً',
            ], 409);
        }

        $collected = CollectedBooths::create([
            'user_id' => $request->user()->id,
            'booth_id' => null,
            'qr_data' => $data['qr_data'],
            'scanned_at' => now(),
        ]);

        return response()->json($collected, 201);
    }
    //=================================================
    //حذف الجناح من المجموعة 

    public function destroy(Request $request, $id)
    {
        $collected = $request->user()->collectedBooths()->findOrFail($id);
        $collected->delete();

        return response()->json(['message' => 'تمت إزالة الكشك من المجموعات']);
    }
}
