<?php

namespace Tests\Feature;

use App\Models\Exhibition;
use App\Models\Organizer;
use App\Models\PortalLink;
use App\Models\StaffMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdministrativePortalStaffAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrative_portal_can_read_staff_tasks_and_payroll_for_its_exhibition(): void
    {
        $organizer = Organizer::factory()->create();
        $exhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);
        $user = User::factory()->create(['role' => 'staff', 'status' => 'approved']);
        $staff = StaffMember::create([
            'user_id' => $user->id,
            'exhibition_id' => $exhibition->id,
            'name' => 'Administrative Portal Staff',
            'team' => 'administrative',
            'salary' => 2500,
        ]);
        $link = PortalLink::create([
            'token' => (string) Str::uuid(),
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'staff_email' => $user->email,
            'exhibition_id' => $exhibition->id,
            'exhibition_name' => $exhibition->name,
            'role' => 'administrative',
            'permissions' => ['admin.staff', 'admin.payroll'],
            'active' => true,
        ]);
        Task::create([
            'exhibition_id' => $exhibition->id,
            'title' => 'Administrative Task',
            'team' => 'administrative',
        ]);

        $headers = ['X-Portal-Token' => $link->token];

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->getJson('/api/staff?team=administrative')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Administrative Portal Staff']);

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->getJson('/api/staff/tasks?exhibition_id=' . $exhibition->id)
            ->assertOk()
            ->assertJsonFragment(['title' => 'Administrative Task']);

        $this->actingAs($user, 'sanctum')->withHeaders($headers)
            ->getJson('/api/staff/payroll?month=2026-08')
            ->assertOk()
            ->assertJsonFragment(['staffName' => 'Administrative Portal Staff']);
    }

    public function test_administrative_portal_can_add_staff_to_its_exhibition(): void
    {
        $organizer = Organizer::factory()->create();
        $exhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);
        $user = User::factory()->create(['role' => 'staff', 'status' => 'approved']);
        $staff = StaffMember::create([
            'user_id' => $user->id,
            'exhibition_id' => $exhibition->id,
            'name' => 'Administrative Portal Staff',
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
            'permissions' => ['admin.staff'],
            'active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Portal-Token' => $link->token])
            ->postJson('/api/staff', [
                'email' => 'new-staff@example.com',
                'phone' => '0500000000',
                'name' => 'New Portal Staff',
                'team' => 'administrative',
                'schedule' => '08:00 - 17:00',
                'workDays' => ['sunday'],
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'New Portal Staff']);

        $this->assertDatabaseHas('staff_members', [
            'exhibition_id' => $exhibition->id,
            'name' => 'New Portal Staff',
            'team' => 'administrative',
        ]);
    }
}
