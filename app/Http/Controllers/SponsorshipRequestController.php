<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use App\Models\SponsorshipRequest;
use App\Http\Requests\StoreSponsorshipRequest;
use App\Http\Requests\UpdateSponsorshipRequest;
use App\Http\Resources\SponsorshipRequestResource;


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
    //===============================================================
}
