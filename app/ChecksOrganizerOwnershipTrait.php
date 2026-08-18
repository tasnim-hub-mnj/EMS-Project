<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\Exhibition;

trait ChecksOrganizerOwnershipTrait
{
    /**
     * Check if the authenticated organizer owns the exhibition.
     */
    public function checkOrganizerExhibition($exhibition_id)
    {
        $organizer = Auth::user()->organizer;

        if (!$organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Organizer account not found.'
            ], 403);
        }

        $exhibition = Exhibition::where('organizer_id', $organizer->id)
            ->where('id', $exhibition_id)
            ->first();

        if (!$exhibition) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this exhibition because it does not belong to you.'
            ], 403);
        }

        return $exhibition;
    }
}
