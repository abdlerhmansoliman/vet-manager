<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_accept_language_header_sets_arabic_locale_and_content_language_header(): void
    {
        $response = $this->withHeader('Accept-Language', 'ar-EG,ar;q=0.9,en;q=0.8')
            ->getJson('/api/v1/auth/resolve-tenants?email=test@example.com');

        $response->assertHeader('Content-Language', 'ar');
        $this->assertSame('ar', App::getLocale());
        $this->assertSame('تم فتح الحالة الطبية بنجاح.', __('messages.cases.created'));
    }

    public function test_accept_language_header_sets_english_locale_and_content_language_header(): void
    {
        $response = $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->getJson('/api/v1/auth/resolve-tenants?email=test@example.com');

        $response->assertHeader('Content-Language', 'en');
        $this->assertSame('en', App::getLocale());
        $this->assertSame('Clinical case opened successfully.', __('messages.cases.created'));
    }

    public function test_explicit_query_param_sets_arabic_locale(): void
    {
        $response = $this->getJson('/api/v1/auth/resolve-tenants?email=test@example.com&lang=ar');

        $response->assertHeader('Content-Language', 'ar');
        $this->assertSame('ar', App::getLocale());
    }

    public function test_default_fallback_is_english_when_no_accept_language_header(): void
    {
        $response = $this->getJson('/api/v1/auth/resolve-tenants?email=test@example.com');

        $response->assertHeader('Content-Language', 'en');
        $this->assertSame('en', App::getLocale());
    }

    public function test_validation_errors_use_arabic_translations_when_header_is_arabic(): void
    {
        $response = $this->withHeader('Accept-Language', 'ar')
            ->withHeader('X-Tenant-ID', 'alphavet')
            ->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('مطلوب', implode(' ', $errors['email'] ?? []));
    }
}
