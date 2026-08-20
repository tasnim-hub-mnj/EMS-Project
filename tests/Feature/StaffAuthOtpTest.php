<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\Exhibition;
use App\Models\Organizer;
use App\Models\PortalLink;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffAuthOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_setup_sends_otp_immediately_to_the_link_email(): void
    {
        Mail::fake();

        $organizerUser = User::factory()->create(['role' => 'organizer']);
        $organizer = Organizer::factory()->create(['user_id' => $organizerUser->id]);
        $exhibition = Exhibition::factory()->create(['organizer_id' => $organizer->id]);
        $staffUser = User::factory()->create([
            'role' => 'staff',
            'email' => 'old-address@example.com',
            'password' => null,
            'is_verified' => false,
        ]);
        $staff = StaffMember::create([
            'user_id' => $staffUser->id,
            'exhibition_id' => $exhibition->id,
            'name' => 'OTP Staff',
            'team' => 'services',
        ]);
        $link = PortalLink::create([
            'token' => (string) Str::uuid(),
            'staff_id' => $staff->id,
            'staff_name' => $staff->name,
            'staff_email' => 'employee@example.com',
            'staff_number' => $staff->number,
            'exhibition_id' => $exhibition->id,
            'exhibition_name' => $exhibition->name,
            'role' => 'services',
            'active' => true,
        ]);

        $response = $this->postJson('/api/staff/auth/setup', [
            'token' => $link->token,
            'email' => 'employee@example.com',
            'password' => 'password-123',
        ]);

        $response->assertCreated()->assertJson(['requiresOtp' => true]);
        Mail::assertSent(VerificationCodeMail::class, function (VerificationCodeMail $mail) {
            return $mail->hasTo('employee@example.com');
        });
    }
}
