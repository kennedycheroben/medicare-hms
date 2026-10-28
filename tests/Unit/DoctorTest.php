<?php

namespace Tests\Unit;

use App\Models\Doctor;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DoctorTest extends TestCase
{
    public function test_image_url_returns_fallback_when_image_is_null_or_empty(): void
    {
        $doctor = new Doctor(['image' => null]);
        $this->assertStringContainsString('assets/img/health/staff-1.webp', $doctor->imageUrl());

        $doctorEmpty = new Doctor(['image' => '']);
        $this->assertStringContainsString('assets/img/health/staff-1.webp', $doctorEmpty->imageUrl());
    }

    public function test_image_url_returns_asset_when_image_starts_with_assets(): void
    {
        $doctor = new Doctor(['image' => 'assets/img/health/staff-2.webp']);
        $this->assertStringContainsString('assets/img/health/staff-2.webp', $doctor->imageUrl());
    }

    public function test_image_url_returns_full_url_when_image_is_http_or_https(): void
    {
        $url = 'https://example.com/doctors/dr-test.jpg';
        $doctor = new Doctor(['image' => $url]);
        $this->assertSame($url, $doctor->imageUrl());
    }

    public function test_image_url_returns_storage_url_when_image_is_stored_on_public_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('doctors/test-photo.jpg', 'fake-image-content');

        $doctor = new Doctor(['image' => 'doctors/test-photo.jpg']);
        $this->assertStringContainsString('storage/doctors/test-photo.jpg', $doctor->imageUrl());
    }

    public function test_image_url_resolves_doctors_prefix_to_storage(): void
    {
        $doctor = new Doctor(['image' => 'doctors/abigael.jpg']);
        $this->assertStringContainsString('storage/doctors/abigael.jpg', $doctor->imageUrl());
    }
}
