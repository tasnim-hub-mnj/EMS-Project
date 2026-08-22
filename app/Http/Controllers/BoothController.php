<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBoothRequest;
use App\Http\Requests\UpdateBoothRequest;
use App\Http\Resources\BoothResource;
use App\Models\BoothBooking;
use App\Models\Booth;
use App\Models\BoothImage;
use App\Models\Exhibition;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use App\Services\NotificationService;

use Illuminate\Support\Facades\Storage;

class BoothController extends Controller
{
    protected function normalizeBoothId($id): int
    {
        if (is_numeric($id)) {
            return (int) $id;
        }

        $normalized = ltrim((string) $id, 'bB');

        return is_numeric($normalized) ? (int) $normalized : 0;
    }

    //===============================================================
    //**************************----o----****************************
    //===============================================================
    protected function resolveSectionForExhibition($exhibitionId, ?string $sectionName): ?Section
    {
        if (blank($sectionName)) {
            return null;
        }

        $normalized = trim($sectionName);

        return Section::firstOrCreate(
            [
                'exhibition_id' => $exhibitionId,
                'name' => $normalized,
            ],
            [
                'type' => 'default',
            ]
        );
    }

    public function store(StoreBoothRequest $request, $exhibition_id)
    {
        // $organizer = Auth::user()->organizer;
        // $exhibition = Exhibition::where('organizer_id', $organizer->id)
        //     ->where('id', $exhibition_id)
        //     ->first();

        // if (!$exhibition)
        // {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'You are not authorized to access this exhibition because it does not belong to you.'
        //     ], 403);
        // }
        $data = $request->validated();
        $data['exhibition_id'] = $exhibition_id;


        $section = $this->resolveSectionForExhibition($exhibition_id, $data['section'] ?? null);
        if ($section) {
            $data['section_id'] = $section->id;
        }

        $booth = Booth::create($data);

        $this->notifyBooth($booth->exhibition_id, 'تمت إضافة جناح جديد', 'تمت إضافة الجناح ' . $booth->number . '.', 'booth.created');

        return new BoothResource($booth->fresh());
    }
    //===========================================
    public function update(UpdateBoothRequest $request, $booth_id)
    {
        $boothId = $this->normalizeBoothId($booth_id);
        $booth = Booth::findOrFail($boothId);

        $data = $request->validated();

        if (array_key_exists('section', $data)) {
            $section = $this->resolveSectionForExhibition($booth->exhibition_id, $data['section'] ?? null);
            if ($section) {
                $data['section_id'] = $section->id;
                $data['section'] = $section->name;
            }
        }

        $booth->update($data);

        $this->notifyBooth($booth->exhibition_id, 'تم تعديل جناح', 'تم تعديل بيانات الجناح ' . $booth->number . '.', 'booth.updated');

        return new BoothResource($booth->fresh());
    }
    //===========================================
    public function index($exhibition_id)
    {
        $booths = Booth::where('exhibition_id', $exhibition_id)
            ->with(['boothImages', 'boothBookings.investor'])
            ->get();

        return BoothResource::collection($booths);
    }
    //===========================================
    public function show($booth_id)
    {
        $boothId = $this->normalizeBoothId($booth_id);
        $booth = Booth::with(['boothImages', 'boothBookings.investor'])
            ->findOrFail($boothId);

        return new BoothResource($booth);
    }
    //===========================================
    public function updateWithImage(Request $request, $booth_id)
    {
        $data = $request->validate([
            'price' => 'nullable|numeric|min:0',
            'pricing_type' => 'nullable|in:total,daily',
            'description' => 'nullable|string|max:500',
            'services' => 'nullable',
            'status' => 'nullable|in:available,unavailable',
            'image' => 'nullable|image',
        ]);

        $boothId = $this->normalizeBoothId($booth_id);
        $booth = Booth::findOrFail($boothId);

        if ($request->hasFile('image'))
        {
            $path = $request->file('image')->store('booths', 'public');

            BoothImage::create([
                'booth_id' => $booth->id,
                'image' => $path
            ]);
        }

        unset($data['image']);
        if (isset($data['services']) && is_string($data['services'])) {
            $decodedServices = json_decode($data['services'], true);
            if (!is_array($decodedServices)) {
                return response()->json(['message' => 'الخدمات المرسلة غير صالحة.'], 422);
            }
            $data['services'] = collect($decodedServices)->mapWithKeys(
                fn ($price, $name) => [(string) $name => max(0, (float) $price)]
            )->all();
        }

        $booth->update($data);

        $this->notifyBooth($booth->exhibition_id, 'تم تعديل جناح', 'تم تعديل بيانات الجناح ' . $booth->number . '.', 'booth.updated');

        return new BoothResource($booth);
    }
    //===========================================
    public function changeStatus(Request $request, $booth_id)
    {
        $request->validate([
            'status' => 'required|in:available,unavailable'
        ]);

        $boothId = $this->normalizeBoothId($booth_id);
        $booth = Booth::findOrFail($boothId);
        $booth->update(['status' => $request->status]);

        $this->notifyBooth($booth->exhibition_id, 'تغيرت حالة جناح', 'تغيرت حالة الجناح ' . $booth->number . '.', 'booth.status');

        return new BoothResource($booth);
    }
    //===========================================
    public function destroy($booth_id)
    {
        $boothId = $this->normalizeBoothId($booth_id);
        $booth = Booth::findOrFail($boothId);
        $exhibitionId = $booth->exhibition_id;
        $boothNumber = $booth->number;
        $booth->delete();

        $this->notifyBooth($exhibitionId, 'تم حذف جناح', 'تم حذف الجناح ' . $boothNumber . '.', 'booth.deleted');

        return response()->json([
            'success' => true,
            'message' => 'Booth deleted successfully'
        ]);
    }

    private function notifyBooth(int $exhibitionId, string $title, string $body, string $event): void
    {
        $exhibition = Exhibition::find($exhibitionId);
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition, $title, $body, 'booth', 'org.booths', ['event' => $event], '/booths', ['org.map', 'admin.map']
            );
        }
    }
    //===========================================

    //===============================================================
    //**************************----i----****************************
    //===============================================================
    public function getAvailableBooths(Request $request)//✅
    {
        $page  = $request->query('page', 1);
        $per_page = $request->query('per_page', 20);

        $exhibition_id = $request->query('exhibition_id');
        $status = $request->query('status');

        $query = Booth::with(['exhibition', 'boothImages']);

        if ($exhibition_id)
        {
            $query->where('exhibition_id', $exhibition_id);
        }

        if ($status)
        {
            $query->where('status_inv', $status);
        }

        $query->orderBy('created_at', 'desc');

        $booths = $query->paginate($per_page, ['*'], 'page', $page);

        $booths_data = $booths->map(function ($booth)
        {
            return
            [
                'id' => $booth->id,
                'number' => $booth->number,
                'exhibition_name' => $booth->exhibition->name,
                'image_url' => $booth->boothImages->first()?->image
                    ? asset('storage/' . $booth->boothImages->first()->image)
                    : null,
                'area' => $booth->area,
                'status' => $booth->status_inv,
                'price' => $booth->price,
                'pricing_type' => $booth->pricing_type,
                'start_date' => Carbon::parse($booth->exhibition->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($booth->exhibition->end_date)->format('Y-m-d'),
                'location' => $booth->location,
                'amenities' => $booth->amenities ?? [],
                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $booth->id)
                    ->where('favoritable_type', Booth::class)
                    ->exists(),

                'services' => $booth->services ?? [],
            ];
        });

        return response()->json([
            'data' => $booths_data,
            'pagination' => [
                'current_page' => $booths->currentPage(),
                'per_page'     => $booths->perPage(),
                'total'        => $booths->total(),
                'last_page'    => $booths->lastPage(),
            ]
        ], 200);
    }
    //==============================================================
    public function getExhibitionBooths(Request $request)//✅
    {
        $exhibition_id = $request->query('exhibition_id');

        if (!$exhibition_id)
        {
            return response()->json(['message' => 'exhibition_id is required'], 400);
        }

        $per_page = $request->query('per_page', 100);

        $booths = Booth::with(['exhibition', 'boothImages', 'boothBookings.investor.user'])
            ->where('exhibition_id', $exhibition_id)
            ->orderBy('number', 'asc')
            ->paginate($per_page);

        $data = $booths->map(function ($booth)
        {
            $services = $booth->services ?? [];
            $amenities = $booth->amenities ?? [];

            //if booked
            $company_name = null;
            $company_email = null;
            $company_initials = null;
            if ($booth->status_inv === 'booked')
            {
                $booking = $booth->boothBookings()->latest()->first();
                if ($booking && $booking->investor)
                {
                    $company_name = $booking->investor->company_name;
                    $company_email = $booking->investor->user->email;
                    $company_initials = mb_substr($company_name, 0, 2);
                }
            }

            return
            [
                'id' => $booth->id,
                'number' => $booth->number,
                'status' => $booth->status_inv,
                'price' => $booth->price,
                'pricing_type' => $booth->pricing_type,
                'area' => $booth->area,

                'image_url' => $booth->boothImages->first()?->image
                    ? asset('storage/' . $booth->boothImages->first()->image)
                    : null,
                'location' => $booth->location,
                'start_date' => Carbon::parse($booth->exhibition->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($booth->exhibition->end_date)->format('Y-m-d'),
                'amenities' => $amenities,

                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $booth->id)
                    ->where('favoritable_type', Booth::class)
                    ->exists(),

                'services' => $services,

                //if booked
                'company_name' => $company_name,
                'company_email' => $company_email,
                'company_initials' => $company_initials,
            ];
        });

        return response()->json([
            'data' => $data
        ], 200);
    }
    //==============================================================
    public function getBoothDetail($booth_id)//✅
    {
        $booth = Booth::with([
        'exhibition',
        'boothImages',
        'boothBookings.investor.user'
        ])->find($booth_id);

        if (!$booth)
        {
            return response()->json(['message' => 'Booth not found'], 404);
        }

        $services = $booth->services ?? [];
        $amenities = $booth->amenities ?? [];

        $company_name = null;
        $company_email = null;
        $company_initials = null;
        if ($booth->status_inv === 'booked')
        {
            $booking = $booth->boothBookings()->latest()->first();
            if ($booking && $booking->investor)
            {
                $company_name = $booking->investor->company_name;
                $company_email = $booking->investor->user->email;
                $company_initials = mb_substr($company_name, 0, 2);
            }
        }

        return response()->json([
            'data' =>
            [
                'id' => $booth->id,
                'number' => $booth->number,
                'status' => $booth->status_inv,
                'price' => $booth->price,
                'pricing_type' => $booth->pricing_type,
                'area' => $booth->area,

                'image_url' => $booth->boothImages->first()?->image
                    ? asset('storage/' . $booth->boothImages->first()->image)
                    : null,
                'location' => $booth->location,
                'start_date' => Carbon::parse($booth->exhibition->start_date)->format('Y-m-d'),
                'end_date' => Carbon::parse($booth->exhibition->end_date)->format('Y-m-d'),
                'amenities' => $amenities,

                'is_favorite' => Auth::user()->favorites()
                    ->where('favoritable_id', $booth->id)
                    ->where('favoritable_type', Booth::class)
                    ->exists(),

                'services' => $services,

                //if booked
                'company_name' => $company_name,
                'company_email' => $company_email,
                'company_initials' => $company_initials,

                'exhibition_id' => $booth->exhibition_id,
                'exhibition_name' => $booth->exhibition->name,
            ]
        ], 200);
    }
    //==============================================================
    //==============================================================

    // public function store(StoreBoothRequest $request, $exhibition_id)
    // {
    //     $exhibition = Exhibition::where('organizer_id', Auth::id())
    //         ->findOrFail($exhibition_id);

    //     $data = $request->validated();

    //     $booth = Booth::create([
    //         'exhibition_id' => $exhibition->id,
    //         'number'        => $data['number'],
    //         'area'          => $data['area'],
    //         'status'        => $data['status'] ?? 'available',
    //         'price'         => $data['price'],
    //         'location'      => $data['location'],
    //         'services'      => isset($data['services'])
    //                             ? json_encode($data['services'])
    //                             : json_encode([]),
    //         'map_x'         => $data['map_x'] ?? null,
    //         'map_y'         => $data['map_y'] ?? null,
    //         'map_z'         => $data['map_z'] ?? null,
    //     ]);

    //     if ($request->hasFile('image'))
    //     {
    //         $path = $request->file('image')->store('booth_images', 'public');
    //         $booth->update(['image' => $path]);
    //     }

    //     $exhibition->increment('total_booths');//+1

    //     return response()->json([
    //         'message' => 'Booth created successfully',
    //         'booth'   => $booth
    //     ], 200);
    // }
    // //=============================================================================
    // public function update(UpdateBoothRequest $request, $exhibition_id, $booth_id)
    // {
    //     $exhibition = Exhibition::where('organizer_id', Auth::id())
    //         ->findOrFail($exhibition_id);

    //     $booth = Booth::where('exhibition_id', $exhibition->id)
    //         ->findOrFail($booth_id);

    //     $data = $request->validated();

    //     // تحويل الخدمات إلى JSON إذا كانت مصفوفة
    //     if (isset($data['services']) && is_array($data['services']))
    //     {
    //         $data['services'] = json_encode($data['services']);
    //     }

    //     if ($request->hasFile('image'))
    //     {
    //         if ($booth->image)
    //         {
    //             Storage::disk('public')->delete($booth->image);
    //         }

    //         $path = $request->file('image')->store('booth_images', 'public');
    //         $data['image'] = $path;
    //     }

    //     $booth->update($data);

    //     return response()->json([
    //         'message' => 'Booth updated successfully',
    //         'booth' => $booth
    //     ], 200);
    // }

    // //=============================================================================
    // public function index($exhibition_id)//عرض كل الاجنحة الخاصة بمعرض معين
    // {
    //     $exhibition = Exhibition::where('organizer_id', Auth::id())
    //     ->findOrFail($exhibition_id);

    //     $booths = Booth::where('exhibition_id', $exhibition->id)->get();

    //     return response()->json([
    //         'booths' => $booths
    //     ], 200);
    // }
    // //=============================================================================
    // public function show($exhibition_id, $booth_id)//عرض جناح معين
    // {
    //     $exhibition = Exhibition::where('organizer_id', Auth::id())
    //     ->findOrFail($exhibition_id);

    //     $booth = Booth::where('exhibition_id', $exhibition->id)
    //     ->findOrFail($booth_id);

    //     return response()->json([
    //         'booth' => $booth
    //     ], 200);
    // }
    // //=============================================================================
    // public function delete($exhibition_id, $booth_id)
    // {
    //     $exhibition = Exhibition::where('organizer_id', Auth::id())
    //         ->findOrFail($exhibition_id);

    //     $booth = Booth::where('exhibition_id', $exhibition->id)
    //         ->findOrFail($booth_id);

    //     $booth->delete();
    //     $exhibition->decrement('total_booths');

    //     return response()->json([
    //         'message' => 'Booth deleted successfully'
    //     ], 200);
    // }






//*****************************************************************************
//**********************************HANAN😁***********************************
//*****************************************************************************


    //===============الزائر======================//
    // عرض الاجنحة كاملة مع امكانية البحث وبجيب الاجنحة مع المعارض المرتبطة فيهن
    public function AllBooths(Request $request)
    {
        $query = Booth::with('exhibition')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('number', 'LIKE', "%$search%")
                    ->orWhere('location', 'LIKE', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('exhibition_id')) {
            $query->where('exhibition_id', $request->input('exhibition_id'));
        }

        return response()->json($query->get());
    }
    //===================================================
    // عرض كشك معين
    public function showBooth($id)
    {
        $booth = Booth::with([
            'exhibition',
            'profile',// 00
            'images',
            'bookings',
            'reviews.user'
        ])->find($id);

        if (!$booth) {
            return response()->json(['message' => 'الكشك غير موجود'], 404);
        }

        return response()->json($booth);
    }


}
