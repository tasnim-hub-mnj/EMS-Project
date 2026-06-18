<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    // عرض حملات المستثمر الخاصة به
    public function index()
    {
        $campaigns = Campaign::where('investor_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'campaigns' => $campaigns
        ], 200);
    }

    // إنشاء حملة جديدة
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string',
            'budget' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $campaign = Campaign::create([
            'investor_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'budget' => $request->budget,
            'status' => $request->status ?? 'active',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json([
            'message' => 'Campaign created successfully',
            'campaign' => $campaign
        ], 201);
    }

    // حذف حملة يخص المستثمر الحالي فقط
    public function destroy($id)
    {
        $campaign = Campaign::where('investor_id', Auth::id())->findOrFail($id);
        $campaign->delete();

        return response()->json([
            'message' => 'Campaign deleted successfully'
        ], 200);
    }
}
