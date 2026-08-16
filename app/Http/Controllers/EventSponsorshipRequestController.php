<?php

namespace App\Http\Controllers;

use App\Models\EventSponsorshipRequest;
use App\Http\Requests\UpdateEventSponsorshipRequestRequest;
use App\Http\Resources\EventSponsorshipRequestResource;

class EventSponsorshipRequestController extends Controller
{
    public function index()
    {
        $requests = EventSponsorshipRequest::with('sponsorEvent')
            ->when(request('exhibition_id'), fn($q) =>
                $q->where('exhibition_id', request('exhibition_id'))
            )
            ->orderByDesc('id')
            ->get();

        return EventSponsorshipRequestResource::collection($requests);
    }
    //===============================================================
    public function update(UpdateEventSponsorshipRequestRequest $request, $event_sponsorship_id)
    {
        $req = EventSponsorshipRequest::findOrFail($event_sponsorship_id);
        $req->update($request->validated());

        return new EventSponsorshipRequestResource($req);
    }
    //===============================================================
}
