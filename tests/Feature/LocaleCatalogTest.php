<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LocaleCatalog;
use App\Support\LocalizedDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Language\Models\Language;
use Tests\TestCase;

class LocaleCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_drives_choices_validation_direction_and_translation_fallback(): void
    {
        Language::create(['name' => 'العربية', 'locale' => 'ar', 'direction' => 'rtl', 'calendar' => 'gregorian', 'first_day_of_week' => 0, 'status' => true]);
        $this->assertContains('ar', LocaleCatalog::codes());
        $this->assertSame('rtl', LocaleCatalog::direction('ar'));
        $this->postJson(route('login.language'), ['locale' => 'ar'])->assertOk();
        $this->get(route('login'))->assertOk()->assertSee('lang="ar"', false)->assertSee('dir="rtl"', false)->assertSee(trans('auth.screen.story_title', [], 'en'));
        Language::where('locale', 'ar')->update(['status' => false]);
        $this->postJson(route('login.language'), ['locale' => 'ar'])->assertUnprocessable();
        $this->get(route('login'))->assertSee('lang="en"', false);
    }

    public function test_last_active_language_cannot_be_disabled_or_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $ids = Language::pluck('id')->all();
        foreach (['delete', 'deactivate'] as $operation) {
            $this->postJson(route('admin.settings.languages.bulk'), compact('ids', 'operation'))->assertUnprocessable();
            $this->assertSame(2, Language::where('status', true)->count());
        }
    }

    public function test_installation_has_bootstrap_languages_without_database_access(): void
    {
        config(['installation.completed' => false]);
        $this->assertSame(['en', 'fa'], LocaleCatalog::codes());
    }

    public function test_dates_follow_the_active_language_calendar_and_digits(): void
    {
        Language::query()->where('locale', 'fa')->update(['calendar' => 'jalali']);

        $this->assertSame('۱۴۰۴/۰۱/۰۱، ۱۳:۲۸', LocalizedDate::format('2025-03-21 13:28:00', locale: 'fa'));
        $this->assertSame('2025/03/21, 13:28', LocalizedDate::format('2025-03-21 13:28:00', locale: 'en'));
    }
}
