<?php

namespace Tests\Feature;

use App\Models\Exhibition;
use App\Models\Organizer;
use App\Models\StaffMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExhibitionDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_tasks_and_payroll_are_scoped_to_the_organizers_exhibition(): void
    {
        $user = User::factory()->create([
            'role' => 'organizer',
            'status' => 'approved',
        ]);
        $organizer = Organizer::factory()->create(['user_id' => $user->id]);
        $firstExhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);
        $otherExhibition = Exhibition::factory()->create();

        $firstStaff = StaffMember::create([
            'user_id' => User::factory()->create(['role' => 'staff'])->id,
            'exhibition_id' => $firstExhibition->id,
            'number' => 's-first',
            'name' => 'First Exhibition Staff',
            'team' => 'services',
            'salary' => 1000,
        ]);
        StaffMember::create([
            'user_id' => User::factory()->create(['role' => 'staff'])->id,
            'exhibition_id' => $otherExhibition->id,
            'number' => 's-other',
            'name' => 'Other Exhibition Staff',
            'team' => 'services',
            'salary' => 9000,
        ]);

        Task::create([
            'exhibition_id' => $firstExhibition->id,
            'title' => 'First Exhibition Task',
            'team' => 'services',
            'assigned_staff_ids' => [$firstStaff->number],
        ]);
        Task::create([
            'exhibition_id' => $otherExhibition->id,
            'title' => 'Other Exhibition Task',
            'team' => 'services',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/staff')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'First Exhibition Staff')
            ->assertJsonMissing(['name' => 'Other Exhibition Staff']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/staff/tasks')
            ->assertOk()
            ->assertJsonFragment(['title' => 'First Exhibition Task'])
            ->assertJsonMissing(['title' => 'Other Exhibition Task']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/staff/payroll?month=2026-08')
            ->assertOk()
            ->assertJsonFragment(['staffName' => 'First Exhibition Staff'])
            ->assertJsonMissing(['staffName' => 'Other Exhibition Staff']);
    }
}
