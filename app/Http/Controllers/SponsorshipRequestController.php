<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use App\Models\SponsorshipRequest;
use App\Http\Requests\StoreSponsorshipRequest;
use App\Http\Requests\UpdateSponsorshipRequest;
use App\Http\Resources\SponsorshipRequestResource;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;


class SponsorshipRequestController extends Controller
{
    public function index()
    {
        $requests = SponsorshipRequest::when(request('exhibition_id'), fn($q) =>
            $q->where('exhibition_id', request('exhibition_id'))
        )->orderByDesc('id')->get();

        return SponsorshipRequestResource::collection($requests);
    }
    //===============================================================
    public function store(StoreSponsorshipRequest $request)
    {
        $data = $request->validated();
        $data['request_date'] = now();

        $req = SponsorshipRequest::create($data);

        return new SponsorshipRequestResource($req);
    }

    public function investorStatus($exhibitionId)
    {
        $investor = Auth::user()->investor;
        $request = SponsorshipRequest::where('investor_id', $investor->id)
            ->where('exhibition_id', $exhibitionId)
            ->first();

        return response()->json([
            'data' => $request ? (new SponsorshipRequestResource($request))->resolve() : null,
        ]);
    }

    public function investorStore(StoreSponsorshipRequest $request)
    {
        $investor = Auth::user()->investor;
        $data = $request->validated();
        $existing = SponsorshipRequest::where('investor_id', $investor->id)
            ->where('exhibition_id', $data['exhibition_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'لديك طلب رعاية سابق لهذا المعرض.',
                'data' => (new SponsorshipRequestResource($existing))->resolve(),
            ], 409);
        }

        $data['investor_id'] = $investor->id;
        $data['sponsor_id'] = null;
        $data['company_name'] = $data['company_name'] ?: $investor->company_name;
        $data['contact_phone'] = $data['contact_phone'] ?: Auth::user()->phone;
        $data['contact_email'] = $data['contact_email'] ?: Auth::user()->email;
        $data['contact_name'] = $data['contact_name'] ?: $investor->company_name;
        $data['request_date'] = now();
        $data['status'] = 'pending';

        $created = SponsorshipRequest::create($data);
        $exhibition = $created->exhibition;
        app(NotificationService::class)->forExhibition(
            $exhibition,
            'طلب رعاية معرض جديد',
            "وصل طلب رعاية جديد للمعرض من {$created->company_name}.",
            'exhibition_sponsorship_request',
            'org.sponsors',
            ['request_id' => $created->id, 'exhibition_id' => $created->exhibition_id],
            '/sponsors',
        );

        return response()->json([
            'data' => (new SponsorshipRequestResource($created))->resolve(),
        ], 201);
    }
    //===============================================================
    public function update(UpdateSponsorshipRequest $request, $sponsorship_id)
    {
        $req = SponsorshipRequest::findOrFail($sponsorship_id);
        $req->update($request->validated());

        return new SponsorshipRequestResource($req);
    }
    //===============================================================
    public function accept($sponsorship_id)
    {
        $req = SponsorshipRequest::findOrFail($sponsorship_id);

        // تحديث حالة الطلب
        $req->update(['status' => 'approved']);

        if ($req->investor_id) {
            app(NotificationService::class)->forUserId(
                (int) $req->investor->user_id,
                'تم قبول طلب رعاية المعرض',
                "تم قبول طلب رعايتك لمعرض {$req->exhibition->name}.",
                'exhibition_sponsorship_approved',
                ['request_id' => $req->id, 'exhibition_id' => $req->exhibition_id],
                null,
                $req->exhibition_id,
            );
        }

        // إنشاء Sponsor جديد
        $sponsor = Sponsor::create([
            'exhibition_id' => $req->exhibition_id,
            'name' => $req->company_name,
            'tier' => $req->proposed_tier,
            'amount' => $req->proposed_amount,
            'status' => 'active',
        ]);

        return response()->json([
            'request' => [
                'id' => 'req-' . $req->id,
                'status' => 'accepted'
            ],
            'sponsor' => [
                'id' => 'sp-' . $sponsor->id,
                'name' => $sponsor->name,
                'tier' => $sponsor->tier,
                'amount' => $sponsor->amount,
                'status' => $sponsor->status
            ]
        ], 200);
    }

    public function reject($sponsorship_id)
    {
        $req = SponsorshipRequest::with('investor', 'exhibition')->findOrFail($sponsorship_id);
        $req->update([
            'status' => 'rejected',
            'reject_reason' => request('reject_reason'),
        ]);

        if ($req->investor_id && $req->investor?->user_id) {
            app(NotificationService::class)->forUserId(
                (int) $req->investor->user_id,
                'تم رفض طلب رعاية المعرض',
                "تم رفض طلب رعايتك لمعرض {$req->exhibition->name}.",
                'exhibition_sponsorship_rejected',
                ['request_id' => $req->id, 'exhibition_id' => $req->exhibition_id],
                null,
                $req->exhibition_id,
            );
        }

        return new SponsorshipRequestResource($req);
    }
    //===============================================================
}
