<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\Exhibition;
use App\Models\Organizer;
use App\Models\PortalLink;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdministrativePortalReportsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrative_portal_can_read_report_data_for_its_exhibition(): void
    {
        $organizer = Organizer::factory()->create();
        $exhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);
        $user = User::factory()->create(['role' => 'staff', 'status' => 'approved']);
        $staff = StaffMember::create([
            'user_id' => $user->id,
            'exhibition_id' => $exhibition->id,
            'name' => 'Report Administrator',
            'team' => 'administrative',
        ]);
        $link = PortalLink::create([
            'token' => (string) Str::uuid(),
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'staff_email' => $user->email,
            'exhibition_id' => $exhibition->id,
            'exhibition_name' => $exhibition->name,
            'role' => 'administrative',
            'permissions' => ['admin.reports'],
            'active' => true,
        ]);
        $copy = Copy::create([
            'exhibition_id' => $exhibition->id,
            'year' => 2026,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-07',
            'copy_status' => 'active',
            'announced' => true,
            'total_booths' => 10,
            'booked_booths' => 2,
            'available_booths' => 8,
            'pending_requests' => 0,
            'visitor_count' => 100,
            'expected_visitors' => 200,
            'turnout_percent' => 50,
            'expected_turnout_percent' => 75,
            'revenue' => 5000,
            'expected_revenue' => 7000,
            'staff_count' => 1,
            'sponsorship_percent' => 20,
            'final_booked_booths' => 2,
        ]);
        $headers = ['X-Portal-Token' => $link->token];

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->getJson('/api/organizer/exhibitions/' . $exhibition->id)
            ->assertOk()
            ->assertJsonPath('data.editions.0.id', $copy->id . '-ed-' . $copy->year);

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->getJson('/api/organizer/reports/edition-comparisons/' . $exhibition->id)
            ->assertOk()
            ->assertJsonFragment(['editionId' => $copy->id . '-ed-' . $copy->year]);

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->getJson('/api/organizer/reports/staff-stats/' . $exhibition->id . '?edition_id=' . $copy->id)
            ->assertOk()
            ->assertJsonPath('totalStaff', 1);

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->get('/api/organizer/reports/archive/export.pdf?exhibitionId=' . $exhibition->id . '&edition_id=' . $copy->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->get('/api/organizer/reports/archive/export.xlsx?exhibitionId=' . $exhibition->id . '&edition_id=' . $copy->id)
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
