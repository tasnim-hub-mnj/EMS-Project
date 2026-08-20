<?php

namespace Tests\Feature;

use App\Models\Exhibition;
use App\Models\ExternalTeam;
use App\Models\Organizer;
use App\Models\PortalLink;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExternalTeamIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_teams_are_scoped_to_the_active_portal_exhibition(): void
    {
        $firstExhibition = Exhibition::factory()->create();
        $otherExhibition = Exhibition::factory()->create();
        $user = User::factory()->create(['role' => 'staff', 'status' => 'approved']);
        $staff = StaffMember::create([
            'user_id' => $user->id,
            'exhibition_id' => $firstExhibition->id,
            'name' => 'External Manager',
            'team' => 'administrative',
        ]);
        $link = PortalLink::create([
            'token' => (string) Str::uuid(),
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'staff_email' => $user->email,
            'exhibition_id' => $firstExhibition->id,
            'exhibition_name' => $firstExhibition->name,
            'role' => 'administrative',
            'permissions' => ['admin.external'],
            'active' => true,
        ]);
        $visibleTeam = ExternalTeam::create([
            'exhibition_id' => $firstExhibition->id,
            'name' => 'First Exhibition Team',
        ]);
        ExternalTeam::create([
            'exhibition_id' => $otherExhibition->id,
            'name' => 'Other Exhibition Team',
        ]);

        $headers = ['X-Portal-Token' => $link->token];

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->getJson('/api/staff/external-teams')
            ->assertOk()
            ->assertJsonFragment(['name' => 'First Exhibition Team'])
            ->assertJsonMissing(['name' => 'Other Exhibition Team']);

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->postJson('/api/staff/external-teams', [
                'exhibition_id' => $otherExhibition->id,
                'name' => 'Created Team',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('external_teams', [
            'name' => 'Created Team',
            'exhibition_id' => $firstExhibition->id,
        ]);

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->putJson('/api/staff/external-teams/ext' . $visibleTeam->id, [
                'name' => 'Updated Team',
            ])
            ->assertOk();

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->putJson('/api/staff/external-teams/ext' . ExternalTeam::where('exhibition_id', $otherExhibition->id)->first()->id, [
                'name' => 'Should Not Update',
            ])
            ->assertNotFound();
    }
}
