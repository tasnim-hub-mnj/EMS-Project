<?php

namespace Tests\Feature;

use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizerRegistrationTest extends TestCase
{
    use RefreshDatabase;
    public function test_organizer_registration_accepts_string_category_and_file_uploads(): void
    {
        Storage::fake('public');

        $response = $this->post('/api/organizer/auth/register', [
            'email' => 'organizer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '966500000001',
            'company_name' => 'Test Company',
            'category' => 'Technology',
            'headquarters' => 'Riyadh',
            'registration_number' => 'REG123',
            'exhibition_location' => 'Jeddah',
            'description' => 'Test organizer',
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
            'legalDocument' => UploadedFile::fake()->create('legal.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'organizer@example.com',
            'role' => 'organizer',
        ]);
        $this->assertDatabaseHas('organizers', [
            'company_name' => 'Test Company',
            'category' => 'Technology',
        ]);
        $this->assertNotEmpty(Storage::disk('public')->allFiles());
    }

    public function test_duplicate_registration_number_is_rejected_without_creating_user(): void
    {
        Storage::fake('public');
        Organizer::factory()->create([
            'reg_number' => 'REG_DUPLICATE',
        ]);

        $response = $this->postJson('/api/organizer/auth/register', [
            'email' => 'duplicate.organizer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '966500000002',
            'company_name' => 'Duplicate Company',
            'category' => 'Technology',
            'headquarters' => 'Riyadh',
            'registration_number' => 'REG_DUPLICATE',
            'exhibition_location' => 'Jeddah',
            'description' => 'Duplicate organizer test',
            'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
            'legalDocument' => UploadedFile::fake()->create('legal.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['registration_number']);
        $this->assertDatabaseMissing('users', [
            'email' => 'duplicate.organizer@example.com',
        ]);
    }

    public function test_organizer_profile_returns_saved_company_data_and_logo_url(): void
    {
        $user = \App\Models\User::factory()->create([
            'email' => 'profile.organizer@example.com',
            'phone' => '966500000003',
            'role' => 'organizer',
        ]);

        $organizer = Organizer::factory()->create([
            'user_id' => $user->id,
            'company_name' => 'Saved Company',
            'category' => 'Technology',
            'headquarters' => 'Riyadh',
            'reg_number' => 'REG_PROFILE_123',
            'location' => 'Jeddah',
            'description' => 'Saved description',
            'logo' => 'organizer_logo/logo.png',
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/organizer/auth/me');

        $response->assertOk();
        $response->assertJsonPath('company_name', 'Saved Company');
        $response->assertJsonPath('phone', '966500000003');
        $response->assertJsonPath('category', 'Technology');
        $response->assertJsonPath('headquarters', 'Riyadh');
        $response->assertJsonPath('registration_number', 'REG_PROFILE_123');
        $response->assertJsonPath('exhibition_location', 'Jeddah');
        $response->assertJsonPath('description', 'Saved description');
        $response->assertJsonPath('logo_url', asset('storage/' . $organizer->logo));
    }
}
