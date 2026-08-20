<?php

namespace Tests\Feature;

use App\Models\Exhibition;
use App\Models\Organizer;
use App\Models\PortalLink;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationFanoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_is_scoped_to_organizer_and_matching_staff_links(): void
    {
        $organizerUser = User::factory()->create(['role' => 'organizer', 'status' => 'approved']);
        $organizer = Organizer::factory()->create(['user_id' => $organizerUser->id]);
        $exhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);

        $staffUser = User::factory()->create(['role' => 'staff', 'status' => 'approved']);
        $staff = StaffMember::create([
            'user_id' => $staffUser->id,
            'exhibition_id' => $exhibition->id,
            'number' => 's-test',
            'name' => 'Test Staff',
            'team' => 'organizational',
        ]);
        PortalLink::create([
            'token' => 'matching-link', 'staff_id' => $staff->id,
            'staff_name' => $staff->name, 'staff_email' => $staffUser->email,
            'exhibition_id' => $exhibition->id, 'exhibition_name' => $exhibition->name,
            'role' => 'organizational', 'permissions' => ['org.map'], 'active' => true,
        ]);

        app(NotificationService::class)->forExhibition(
            $exhibition, 'Map published', 'Map is ready', 'map', 'org.map'
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $organizerUser->id, 'exhibition_id' => $exhibition->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $staffUser->id, 'portal_link_id' => $staff->portalLinks()->first()->id,
            'permission_key' => 'org.map',
        ]);
    }

    public function test_new_employee_with_same_permission_receives_the_same_exhibition_notification(): void
    {
        $organizerUser = User::factory()->create(['role' => 'organizer', 'status' => 'approved']);
        $organizer = Organizer::factory()->create(['user_id' => $organizerUser->id]);
        $exhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);

        $matchingUsers = User::factory()->count(2)->create(['role' => 'staff', 'status' => 'approved']);
        $nonMatchingUser = User::factory()->create(['role' => 'staff', 'status' => 'approved']);

        foreach ($matchingUsers as $index => $user) {
            $staff = StaffMember::create([
                'user_id' => $user->id,
                'exhibition_id' => $exhibition->id,
                'number' => 's-map-' . $index,
                'name' => 'Map Staff ' . $index,
                'team' => 'organizational',
            ]);
            PortalLink::create([
                'token' => 'map-link-' . $index,
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'staff_email' => $user->email,
                'exhibition_id' => $exhibition->id,
                'exhibition_name' => $exhibition->name,
                'role' => 'organizational',
                'permissions' => ['org.map'],
                'messaging_channels' => ['team-org'],
                'active' => true,
            ]);
        }

        $nonMatchingStaff = StaffMember::create([
            'user_id' => $nonMatchingUser->id,
            'exhibition_id' => $exhibition->id,
            'number' => 's-no-map',
            'name' => 'No Map Staff',
            'team' => 'organizational',
        ]);
        PortalLink::create([
            'token' => 'no-map-link',
            'staff_id' => $nonMatchingStaff->id,
            'staff_name' => $nonMatchingStaff->name,
            'staff_email' => $nonMatchingUser->email,
            'exhibition_id' => $exhibition->id,
            'exhibition_name' => $exhibition->name,
            'role' => 'organizational',
            'permissions' => ['org.tasks'],
            'active' => true,
        ]);

        app(NotificationService::class)->forExhibition(
            $exhibition,
            'تم نشر الخريطة',
            'تم نشر خريطة المعرض بنجاح.',
            'map',
            'org.map'
        );

        $this->assertDatabaseCount('notifications', 3);
        foreach ($matchingUsers as $user) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $user->id,
                'exhibition_id' => $exhibition->id,
                'permission_key' => 'org.map',
            ]);
        }
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $nonMatchingUser->id,
            'permission_key' => 'org.map',
        ]);
    }

    public function test_staff_without_a_portal_link_receives_a_direct_notification(): void
    {
        $organizerUser = User::factory()->create(['role' => 'organizer', 'status' => 'approved']);
        $organizer = Organizer::factory()->create(['user_id' => $organizerUser->id]);
        $exhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);
        $staffUser = User::factory()->create(['role' => 'staff', 'status' => 'approved']);

        StaffMember::create([
            'user_id' => $staffUser->id,
            'exhibition_id' => $exhibition->id,
            'number' => 's-admin',
            'name' => 'Admin Staff',
            'team' => 'administrative',
        ]);

        app(NotificationService::class)->forExhibition(
            $exhibition,
            'تمت إضافة موظف جديد',
            'تمت إضافة موظف إداري.',
            'staff',
            'admin.staff'
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $staffUser->id,
            'exhibition_id' => $exhibition->id,
            'portal_link_id' => null,
            'permission_key' => 'admin.staff',
        ]);
    }

    public function test_administrative_portal_can_read_its_staff_notification_using_the_portal_header(): void
    {
        $organizer = Organizer::factory()->create();
        $exhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);
        $staffUser = User::factory()->create(['role' => 'staff', 'status' => 'approved']);
        $staff = StaffMember::create([
            'user_id' => $staffUser->id,
            'exhibition_id' => $exhibition->id,
            'number' => 's-admin-link',
            'name' => 'Administrative Link Staff',
            'team' => 'administrative',
        ]);
        $link = PortalLink::create([
            'token' => (string) Str::uuid(),
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'staff_email' => $staffUser->email,
            'exhibition_id' => $exhibition->id,
            'exhibition_name' => $exhibition->name,
            'role' => 'administrative',
            'permissions' => ['admin.staff'],
            'active' => true,
        ]);

        app(NotificationService::class)->forExhibition(
            $exhibition,
            'تمت إضافة موظف جديد',
            'تمت إضافة موظف إداري.',
            'staff',
            'admin.staff'
        );

        $this->actingAs($staffUser, 'sanctum')
            ->withHeaders(['X-Portal-Token' => $link->token])
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonFragment(['permissionKey' => 'admin.staff']);
    }

    public function test_event_notifications_only_reach_staff_with_the_matching_permission(): void
    {
        $organizerUser = User::factory()->create(['role' => 'organizer', 'status' => 'approved']);
        $organizer = Organizer::factory()->create(['user_id' => $organizerUser->id]);
        $exhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);
        $matchingUser = User::factory()->create(['role' => 'staff', 'status' => 'approved']);
        $otherUser = User::factory()->create(['role' => 'staff', 'status' => 'approved']);

        foreach ([[$matchingUser, ['org.events']], [$otherUser, ['org.bookings']]] as [$user, $permissions]) {
            $staff = StaffMember::create([
                'user_id' => $user->id,
                'exhibition_id' => $exhibition->id,
                'number' => 's-event-' . $user->id,
                'name' => 'Event Staff ' . $user->id,
                'team' => 'organizational',
            ]);
            PortalLink::create([
                'token' => (string) Str::uuid(),
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'staff_email' => $user->email,
                'exhibition_id' => $exhibition->id,
                'exhibition_name' => $exhibition->name,
                'role' => 'organizational',
                'permissions' => $permissions,
                'active' => true,
            ]);
        }

        app(NotificationService::class)->forExhibition(
            $exhibition, 'تم نشر فعالية', 'تم نشر فعالية جديدة.', 'event', 'org.events'
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $matchingUser->id,
            'permission_key' => 'org.events',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $otherUser->id,
            'permission_key' => 'org.events',
        ]);
    }

    public function test_exhibition_status_notification_is_created_only_when_status_changes(): void
    {
        $organizerUser = User::factory()->create(['role' => 'organizer', 'status' => 'approved']);
        $organizer = Organizer::factory()->create(['user_id' => $organizerUser->id]);
        $exhibition = Exhibition::factory()->create([
            'organizer_id' => $organizer->id,
            'start_date' => Carbon::tomorrow()->addDays(10),
            'end_date' => Carbon::tomorrow()->addDays(12),
            'status' => 'far',
        ]);

        Artisan::call('app:update-exhibition-status');
        $this->assertDatabaseHas('notifications', [
            'user_id' => $organizerUser->id,
            'permission_key' => 'admin.company',
            'type' => 'exhibition_status',
        ]);
        $count = \App\Models\Notification::count();

        Artisan::call('app:update-exhibition-status');
        $this->assertDatabaseCount('notifications', $count);
    }

    public function test_periodic_report_notification_reaches_report_staff(): void
    {
        $organizerUser = User::factory()->create(['role' => 'organizer', 'status' => 'approved']);
        $organizer = Organizer::factory()->create(['user_id' => $organizerUser->id]);
        $exhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);
        $reportUser = User::factory()->create(['role' => 'staff', 'status' => 'approved']);
        $otherUser = User::factory()->create(['role' => 'staff', 'status' => 'approved']);

        foreach ([[$reportUser, ['admin.reports']], [$otherUser, ['admin.staff']]] as [$user, $permissions]) {
            $staff = StaffMember::create([
                'user_id' => $user->id,
                'exhibition_id' => $exhibition->id,
                'number' => 's-report-' . $user->id,
                'name' => 'Report Staff ' . $user->id,
                'team' => 'administrative',
            ]);
            PortalLink::create([
                'token' => (string) Str::uuid(),
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'staff_email' => $user->email,
                'exhibition_id' => $exhibition->id,
                'exhibition_name' => $exhibition->name,
                'role' => 'administrative',
                'permissions' => $permissions,
                'active' => true,
            ]);
        }

        Artisan::call('app:send-exhibition-report-notifications');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $organizerUser->id,
            'permission_key' => 'admin.reports',
            'type' => 'report',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $reportUser->id,
            'permission_key' => 'admin.reports',
            'type' => 'report',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $otherUser->id,
            'permission_key' => 'admin.reports',
        ]);
    }
}
