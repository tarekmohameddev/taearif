<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Package;
use App\Models\Membership;
use App\Models\User\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyVideoSizeLimitTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $package;
    protected $membership;
    protected $defaultLanguage;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create();
        
        // Create test package with video size limit
        $this->package = Package::create([
            'title' => 'Test Package',
            'icon' => 'test-icon',
            'subtitle' => 'Test Subtitle',
            'slug' => 'test-package',
            'price' => 100,
            'term' => 'monthly',
            'featured' => 0,
            'is_trial' => 0,
            'trial_days' => 0,
            'status' => 1,
            'is_active' => 1,
            'new_features' => 'Test features',
            'features' => 'Test features',
            'meta_keywords' => 'test',
            'meta_description' => 'test',
            'number_of_vcards' => 10,
            'project_limit_number' => 5,
            'real_estate_limit_number' => 10,
            'video_size_limit' => 50, // 50MB limit
            'file_size_limit' => 100,
            'serial_number' => 1
        ]);
        
        // Create active membership
        $this->membership = Membership::create([
            'user_id' => $this->user->id,
            'package_id' => $this->package->id,
            'status' => 1,
            'start_date' => now(),
            'expire_date' => now()->addMonth()
        ]);
        
        // Create default language
        $this->defaultLanguage = Language::create([
            'user_id' => $this->user->id,
            'name' => 'English',
            'code' => 'en',
            'is_default' => 1,
            'direction' => 'ltr'
        ]);
    }

    /** @test */
    public function it_allows_video_upload_within_package_limit()
    {
        Storage::fake('public');
        
        // Create a video file that's within the 50MB limit (let's say 30MB)
        $videoFile = UploadedFile::fake()->create('test-video.mp4', 30 * 1024); // 30MB in KB
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/properties', [
                'title' => 'Test Property',
                'address' => 'Test Address',
                'description' => 'Test Description',
                'featured_image' => 'test-image.jpg',
                'video_file' => $videoFile,
                'price' => 100000,
                'beds' => 3,
                'bath' => 2,
                'area' => 150,
                'purpose' => 'sale',
                'type' => 'apartment',
                'status' => 1,
                'latitude' => 25.2048,
                'longitude' => 55.2708,
                'city_id' => 1,
                'state_id' => 1,
                'category_id' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Property created successfully'
            ]);
    }

    /** @test */
    public function it_rejects_video_upload_exceeding_package_limit()
    {
        Storage::fake('public');
        
        // Create a video file that exceeds the 50MB limit (let's say 75MB)
        $videoFile = UploadedFile::fake()->create('large-video.mp4', 75 * 1024); // 75MB in KB
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/properties', [
                'title' => 'Test Property',
                'address' => 'Test Address',
                'description' => 'Test Description',
                'featured_image' => 'test-image.jpg',
                'video_file' => $videoFile,
                'price' => 100000,
                'beds' => 3,
                'bath' => 2,
                'area' => 150,
                'purpose' => 'sale',
                'type' => 'apartment',
                'status' => 1,
                'latitude' => 25.2048,
                'longitude' => 55.2708,
                'city_id' => 1,
                'state_id' => 1,
                'category_id' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'fail'
            ])
            ->assertJsonValidationErrors(['video_file']);
    }

    /** @test */
    public function it_shows_custom_error_message_with_file_size_and_limit()
    {
        Storage::fake('public');
        
        // Create a video file that exceeds the limit
        $videoFile = UploadedFile::fake()->create('large-video.mp4', 75 * 1024); // 75MB in KB
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/properties', [
                'title' => 'Test Property',
                'address' => 'Test Address',
                'description' => 'Test Description',
                'featured_image' => 'test-image.jpg',
                'video_file' => $videoFile,
                'price' => 100000,
                'beds' => 3,
                'bath' => 2,
                'area' => 150,
                'purpose' => 'sale',
                'type' => 'apartment',
                'status' => 1,
                'latitude' => 25.2048,
                'longitude' => 55.2708,
                'city_id' => 1,
                'state_id' => 1,
                'category_id' => 1,
            ]);

        $response->assertStatus(422);
        
        $errors = $response->json('errors');
        $this->assertArrayHasKey('video_file', $errors);
        $this->assertStringContainsString('75MB', $errors['video_file'][0]);
        $this->assertStringContainsString('50MB', $errors['video_file'][0]);
    }

    /** @test */
    public function it_allows_video_upload_when_no_package_limit_is_set()
    {
        // Update package to have no video size limit
        $this->package->update(['video_size_limit' => null]);
        
        Storage::fake('public');
        
        // Create a large video file
        $videoFile = UploadedFile::fake()->create('large-video.mp4', 100 * 1024); // 100MB in KB
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/properties', [
                'title' => 'Test Property',
                'address' => 'Test Address',
                'description' => 'Test Description',
                'featured_image' => 'test-image.jpg',
                'video_file' => $videoFile,
                'price' => 100000,
                'beds' => 3,
                'bath' => 2,
                'area' => 150,
                'purpose' => 'sale',
                'type' => 'apartment',
                'status' => 1,
                'latitude' => 25.2048,
                'longitude' => 55.2708,
                'city_id' => 1,
                'state_id' => 1,
                'category_id' => 1,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Property created successfully'
            ]);
    }

    /** @test */
    public function it_validates_video_size_limit_on_property_update()
    {
        // First create a property
        $property = \App\Models\User\RealestateManagement\Property::create([
            'user_id' => $this->user->id,
            'region_id' => 1,
            'price' => 100000,
            'purpose' => 'sale',
            'type' => 'apartment',
            'beds' => 3,
            'bath' => 2,
            'area' => 150,
            'status' => 1,
            'featured_image' => 'test-image.jpg',
            'featured' => 0
        ]);

        Storage::fake('public');
        
        // Try to update with a video that exceeds the limit
        $videoFile = UploadedFile::fake()->create('large-video.mp4', 75 * 1024); // 75MB in KB
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/properties/{$property->id}", [
                'title' => 'Updated Property',
                'address' => 'Updated Address',
                'description' => 'Updated Description',
                'featured_image' => 'test-image.jpg',
                'video_file' => $videoFile,
                'price' => 150000,
                'beds' => 4,
                'bath' => 3,
                'area' => 200,
                'purpose' => 'rent',
                'type' => 'villa',
                'status' => 1,
                'latitude' => 25.2048,
                'longitude' => 55.2708,
                'city_id' => 1,
                'state_id' => 1,
                'category_id' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'fail'
            ])
            ->assertJsonValidationErrors(['video_file']);
    }

    /** @test */
    public function it_allows_video_update_within_package_limit()
    {
        // First create a property
        $property = \App\Models\User\RealestateManagement\Property::create([
            'user_id' => $this->user->id,
            'region_id' => 1,
            'price' => 100000,
            'purpose' => 'sale',
            'type' => 'apartment',
            'beds' => 3,
            'bath' => 2,
            'area' => 150,
            'status' => 1,
            'featured_image' => 'test-image.jpg',
            'featured' => 0
        ]);

        Storage::fake('public');
        
        // Update with a video that's within the limit
        $videoFile = UploadedFile::fake()->create('test-video.mp4', 30 * 1024); // 30MB in KB
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/properties/{$property->id}", [
                'title' => 'Updated Property',
                'address' => 'Updated Address',
                'description' => 'Updated Description',
                'featured_image' => 'test-image.jpg',
                'video_file' => $videoFile,
                'price' => 150000,
                'beds' => 4,
                'bath' => 3,
                'area' => 200,
                'purpose' => 'rent',
                'type' => 'villa',
                'status' => 1,
                'latitude' => 25.2048,
                'longitude' => 55.2708,
                'city_id' => 1,
                'state_id' => 1,
                'category_id' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Property updated successfully'
            ]);
    }

    /** @test */
    public function it_handles_different_package_limits_correctly()
    {
        // Create a package with a smaller limit (25MB)
        $smallPackage = Package::create([
            'title' => 'Small Package',
            'icon' => 'small-icon',
            'subtitle' => 'Small Subtitle',
            'slug' => 'small-package',
            'price' => 50,
            'term' => 'monthly',
            'featured' => 0,
            'is_trial' => 0,
            'trial_days' => 0,
            'status' => 1,
            'is_active' => 1,
            'new_features' => 'Small features',
            'features' => 'Small features',
            'meta_keywords' => 'small',
            'meta_description' => 'small',
            'number_of_vcards' => 5,
            'project_limit_number' => 2,
            'real_estate_limit_number' => 5,
            'video_size_limit' => 25, // 25MB limit
            'file_size_limit' => 50,
            'serial_number' => 2
        ]);

        // Update user's membership to use the smaller package
        $this->membership->update(['package_id' => $smallPackage->id]);

        Storage::fake('public');
        
        // Try to upload a 30MB video (should fail with 25MB limit)
        $videoFile = UploadedFile::fake()->create('medium-video.mp4', 30 * 1024); // 30MB in KB
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/properties', [
                'title' => 'Test Property',
                'address' => 'Test Address',
                'description' => 'Test Description',
                'featured_image' => 'test-image.jpg',
                'video_file' => $videoFile,
                'price' => 100000,
                'beds' => 3,
                'bath' => 2,
                'area' => 150,
                'purpose' => 'sale',
                'type' => 'apartment',
                'status' => 1,
                'latitude' => 25.2048,
                'longitude' => 55.2708,
                'city_id' => 1,
                'state_id' => 1,
                'category_id' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['video_file']);

        $errors = $response->json('errors');
        $this->assertStringContainsString('25MB', $errors['video_file'][0]);
    }
}
