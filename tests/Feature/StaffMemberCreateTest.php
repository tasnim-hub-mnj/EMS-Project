<?php

namespace Tests\Feature;

use App\Models\Exhibition;
use App\Models\Organizer;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffMemberCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_create_staff_from_frontend_payload(): void
    {
        $user = User::factory()->create([
            'email' => 'organizer@example.com',
            'phone' => '0500000001',
            'role' => 'organizer',
            'status' => 'approved',
        ]);

        $organizer = Organizer::create([
            'user_id' => $user->id,
            'company_name' => 'ExpoWorks',
            'category' => 'Technology',
            'headquarters' => 'Riyadh',
            'reg_number' => 'REG-001',
            'location' => 'Riyadh',
            'description' => 'Test organizer',
        ]);

        $exhibition = Exhibition::create([
            'organizer_id' => $organizer->id,
            'name' => 'Tech Expo',
            'type' => 'Technology',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'location' => 'Riyadh',
            'city' => 'Riyadh',
            'status' => 'upcoming',
            'available_booths' => 10,
            'total_booths' => 10,
            'total_sponser_events' => 0,
            'visitors_count' => 0,
        ]);

        $payload = [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed.staff@example.com',
            'phone' => '0501234567',
            'role' => 'Supervisor',
            'rank' => 'Senior',
            'team' => 'service',
            'type' => 'permanent',
            'schedule' => '08:00 - 17:00',
            'national_id' => '1234567890',
            'attendance_rate' => 0,
            'tasks_completed' => 0,
            'tasks_total' => 0,
            'salary' => 1200,
            'payment_period' => 'biweekly',
            'work_days' => ['sun', 'mon'],
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/staff', $payload);

        $response->assertCreated();

        $staff = StaffMember::query()->where('name', 'Ahmed Ali')->first();
        $this->assertNotNull($staff);
        $this->assertSame($exhibition->id, $staff->exhibition_id);
        $this->assertSame('services', $staff->team);
        $this->assertSame('bi-weekly', $staff->paymentPeriod);
        $this->assertSame('1234567890', $staff->nationalId);
        $this->assertSame(['sun', 'mon'], $staff->workDays);
        $this->assertDatabaseHas('users', ['email' => 'ahmed.staff@example.com', 'phone' => '0501234567', 'role' => 'staff']);
    }
}
