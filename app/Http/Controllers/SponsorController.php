<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use App\Http\Requests\StoreSponsorRequest;
use App\Http\Requests\UpdateSponsorRequest;
use App\Http\Resources\SponsorResource;
use App\Models\Exhibition;
use App\Services\NotificationService;

class SponsorController extends Controller
{
    public function index()
    {
        $sponsors = Sponsor::when(request('exhibition_id'), fn($q) =>
            $q->where('exhibition_id', request('exhibition_id'))
        )->orderByDesc('id')->get();

        return SponsorResource::collection($sponsors);
    }
    //===============================================================
    public function show($sponsor_id)
    {
        return new SponsorResource(Sponsor::findOrFail($sponsor_id));
    }
    //===============================================================
    public function store(StoreSponsorRequest $request)
    {
        $data = $request->validated();

        if (isset($data['logo'])) {
            $data['logo'] = $data['logo']->store('sponsors', 'public');
        }

        $sponsor = Sponsor::create($data);

        $this->notifySponsor($sponsor->exhibition_id, 'تمت إضافة راعٍ جديد', 'تمت إضافة راعٍ جديد إلى المعرض.', 'sponsor.created');

        return new SponsorResource($sponsor);
    }
    //===============================================================
    public function update(UpdateSponsorRequest $request, $sponsor_id)
    {
        $sponsor = Sponsor::findOrFail($sponsor_id);
        $data = $request->validated();

        if (isset($data['logo'])) {
            $data['logo'] = $data['logo']->store('sponsors', 'public');
        }

        $sponsor->update($data);

        $this->notifySponsor($sponsor->exhibition_id, 'تم تعديل بيانات راعٍ', 'تم تعديل بيانات أحد الرعاة.', 'sponsor.updated');

        return new SponsorResource($sponsor);
    }
    //===============================================================
    public function delete($sponsor_id)
    {
        $sponsor = Sponsor::findOrFail($sponsor_id);
        $exhibitionId = $sponsor->exhibition_id;
        $sponsor->delete();
        $this->notifySponsor($exhibitionId, 'تم حذف راعٍ', 'تم حذف راعٍ من المعرض.', 'sponsor.deleted');

        return response()->json(['message' => 'Sponsor deleted'], 200);
    }

    private function notifySponsor(int $exhibitionId, string $title, string $body, string $event): void
    {
        $exhibition = Exhibition::find($exhibitionId);
        if ($exhibition) {
            app(NotificationService::class)->forExhibition(
                $exhibition, $title, $body, 'sponsor', 'org.sponsors', ['event' => $event], '/sponsors', ['org.events']
            );
        }
    }
    //===============================================================


}
