<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SponsorshipRequest extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'exhibition_id',
        'sponsor_id',
        'company_name',
        'company_type',
        'proposed_tier',
        'status',
        'proposed_amount',
        'start_date',
        'end_date',
        'book_at',
        'contact_name',
        'offer_details',
        'conditions',
        'contract_terms',
        'organizer_notes',
        'last_sponsor',
        'reject_reason',

        'website',
        'contact_phone',
        'contact_email',
        'request_date'
    ];

    protected $table = 'sponsorship_requests';
}
