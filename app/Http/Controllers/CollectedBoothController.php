<?php

namespace App\Http\Controllers;

use App\Models\Booth;
use App\Models\CollectedBooths;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectedBoothController extends Controller
{
    public function index(Request $request) //عرض كل الاجنحة المحفوظة
    {
        $visitor = $request->user()?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        // جلب سجلات الأجنحة المجمعة للزائر مع بيانات الأكشاك المرتبطة بها
        $collectedBooths = CollectedBooths::with('booth')
            ->where('visitor_id', $visitor->id)
            ->get();

        $boothsList = [];

        foreach ($collectedBooths as $collected) {
            $booth = $collected->booth;

            if ($booth) {
                $amenities = $booth->services;
                if (is_string($amenities)) {
                    $amenities = json_decode($amenities, true);
                }

                $boothsList[] = [
                    'id' => (int) $booth->id,
                    'number' => (string) $booth->number,
                    'col' => (int) ($booth->map_x ?? 0),
                    'row' => (int) ($booth->map_y ?? 0),
                    'width' => (int) ($booth->area ?? 1),
                    'depth' => (int) ($booth->map_z ?? 1),
                    'height' => 1.5,
                    'status' => ($booth->status === 'available') ? 'available' : 'booked',
                    'price' => (float) $booth->price,
                    'area' => (float) $booth->area,
                    'amenities' => is_array($amenities) ? array_values($amenities) : [],
                ];
            }
        }

        return response()->json([
            'status' => true,
            'data' => $boothsList
        ], 200);
    }
    //==============================================

    public function store(Request $request)
    {
        $data = $request->validate([
            'booth_id' => 'required|exists:booths,id',
        ]);

        $user = $request->user();
        $visitor = $user?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $exists = CollectedBooths::where('visitor_id', $visitor->id)
            ->where('booth_id', $data['booth_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'هذا الجناح مضاف مسبقاً في قائمتك المجمعة',
            ], 409);
        }

        $booth = Booth::find($data['booth_id']);

        $collected = CollectedBooths::create([
            'visitor_id' => $visitor->id,
            'booth_id' => $booth->id,
            'qr_data' => (string) $booth->number,
            'scanned_at' => now(),
        ]);

        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'إضافة جناح جديد',
            'body' => "تم مسح وإضافة الجناح رقم ({$booth->number}) بنجاح إلى قائمتك المجمعة",
            'type' => 'booth',
            'read' => false,
            'data' => [
                'booth_id' => $booth->id,
                'booth_number' => $booth->number,
                'collected_id' => $collected->id,
            ],
            'action_url' => "/collected-booths/{$collected->id}",
        ]);

        $amenities = $booth->services;
        if (is_string($amenities)) {
            $amenities = json_decode($amenities, true);
        }

        $boothData = [
            'id' => (int) $booth->id,
            'number' => (string) $booth->number,
            'col' => (int) ($booth->map_x ?? 0),
            'row' => (int) ($booth->map_y ?? 0),
            'width' => (int) ($booth->area ?? 1),
            'depth' => (int) ($booth->map_z ?? 1),
            'height' => 1.5,
            'status' => ($booth->status === 'available') ? 'available' : 'booked',
            'price' => (float) $booth->price,
            'area' => (float) $booth->area,
            'amenities' => is_array($amenities) ? array_values($amenities) : [],
        ];

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة الجناح للقائمة المجمعة بنجاح',
            'data' => $boothData
        ], 201);
    }

    //================================================
    //مسح qr الجناح

    public function scan(Request $request)
    {
        $data = $request->validate([
            'qr_data' => 'required|string',
        ]);

        $user = $request->user();
        $visitor = $user?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $booth = Booth::where('id', $data['qr_data'])
            ->orWhere('number', $data['qr_data'])
            ->first();

        $exists = CollectedBooths::where('visitor_id', $visitor->id)
            ->where(function ($query) use ($data, $booth) {
                $query->where('qr_data', $data['qr_data']);
                if ($booth) {
                    $query->orWhere('booth_id', $booth->id);
                }
            })
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'تم مسح هذا الكود مسبقاً',
            ], 409);
        }

        $collected = CollectedBooths::create([
            'visitor_id' => $visitor->id,
            'booth_id' => $booth?->id,
            'qr_data' => $data['qr_data'],
            'scanned_at' => now(),
        ]);
        $boothIdentifier = $booth?->number ?? $data['qr_data'];

        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'مسح رمز الجناح',
            'body' => "تم مسح كود الجناح ({$boothIdentifier}) بنجاح وإضافته لقائمتك",
            'type' => 'booth_scan',
            'read' => false,
            'data' => [
                'booth_id' => $booth?->id,
                'qr_data' => $data['qr_data'],
                'collected_id' => $collected->id,
            ],
            'action_url' => "/collected-booths/{$collected->id}",
        ]);

        $amenities = $booth?->services;
        if (is_string($amenities)) {
            $amenities = json_decode($amenities, true);
        }

        $boothData = $booth ? [
            'id' => (int) $booth->id,
            'number' => (string) $booth->number,
            'col' => (int) ($booth->map_x ?? 0),
            'row' => (int) ($booth->map_y ?? 0),
            'width' => (int) ($booth->area ?? 1),
            'depth' => (int) ($booth->map_z ?? 1),
            'height' => 1.5,
            'status' => ($booth->status === 'available') ? 'available' : 'booked',
            'price' => (float) $booth->price,
            'area' => (float) $booth->area,
            'amenities' => is_array($amenities) ? array_values($amenities) : [],
        ] : null;

        return response()->json([
            'status' => true,
            'message' => 'تم تجميع الجناح بنجاح',
            'data' => $boothData
        ], 201);
    }
    //=================================================

    //=================================================
    //حذف الجناح من المجموعة 
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $visitor = $user?->visitor;

        if (!$visitor) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك، يجب تسجيل الدخول كزائر'
            ], 403);
        }

        $collected = CollectedBooths::where('visitor_id', $visitor->id)
            ->where(function ($query) use ($id) {
                $query->where('booth_id', $id)
                    ->orWhere('id', $id);
            })
            ->first();

        if (!$collected) {
            return response()->json([
                'status' => false,
                'message' => 'الجناح غير موجود ضمن قائمتك المجمعة'
            ], 404);
        }

        $boothNumber = $collected->booth?->number ?? $collected->qr_data ?? '';

        $collected->delete();
        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'إزالة جناح مجمع',
            'body' => $boothNumber
                ? "تمت إزالة الجناح رقم ({$boothNumber}) من قائمتك المجمعة"
                : "تمت إزالة الجناح من قائمتك المجمعة بنجاح",
            'type' => 'booth_remove',
            'read' => false,
            'data' => [
                'booth_id' => $collected->booth_id,
            ],
            'action_url' => "/collected-booths",
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إزالة الجناح المجمّع بنجاح'
        ], 200);
    }
}

