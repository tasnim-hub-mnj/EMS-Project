<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use App\Http\Requests\StoreSponsorRequest;
use App\Http\Requests\UpdateSponsorRequest;
use App\Http\Resources\SponsorResource;

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

        return new SponsorResource($sponsor);
    }
    //===============================================================
    public function delete($sponsor_id)
    {
        Sponsor::findOrFail($sponsor_id)->delete();

        return response()->json(['message' => 'Sponsor deleted'], 200);
    }
    //===============================================================


}
