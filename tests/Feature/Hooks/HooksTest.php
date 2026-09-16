<?php

declare(strict_types=1);

namespace Tests\Feature\Hooks;

use App\Facades\Hooks;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class HooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_preserve_priority_and_registration_order(): void
    {
        Hooks::addFilter('test.value', fn ($value) => $value.'c', 20);
        Hooks::addFilter('test.value', fn ($value) => $value.'a', 5);
        Hooks::addFilter('test.value', fn ($value) => $value.'b', 5);
        $this->assertSame('abc', Hooks::filter('test.value', ''));
        $this->assertSame('unchanged', Hooks::filter('missing', 'unchanged'));
    }

    public function test_settings_hook_can_veto_without_saving_or_notifying(): void
    {
        $user = User::factory()->create();
        $saved = false;
        Hooks::addAction('jobic.settings.profile.saving', fn () => throw ValidationException::withMessages(['name' => 'Veto']));
        Hooks::addAction('jobic.settings.profile.saved', function () use (&$saved): void {
            $saved = true;
        });
        $this->actingAs($user)->put(route('admin.settings.profile.update'), ['name' => 'Changed', 'email' => 'changed@example.test'])->assertSessionHasErrors('name');
        $this->assertNotSame('Changed', $user->fresh()->name);
        $this->assertFalse($saved);
    }

    public function test_security_hooks_exclude_secrets_and_observer_failure_does_not_fail_save(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $contexts = [];
        Log::shouldReceive('error')->once()->with('CMS hook observer failed.', ['hook' => 'jobic.settings.security.saved', 'exception_type' => RuntimeException::class]);
        Hooks::addAction('jobic.settings.security.saved', fn () => throw new RuntimeException('secret must not be logged'));
        Hooks::addAction('jobic.settings.security.saved', function ($context) use (&$contexts): void {
            $contexts[] = $context;
        });
        $this->actingAs($user)->put(route('admin.settings.security.update'), ['password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertSessionHas('status');
        $this->assertCount(1, $contexts);
        $this->assertSame(['section', 'user_id', 'fields'], array_keys($contexts[0]));
        $this->assertStringNotContainsString('old-password', json_encode($contexts));
        $this->assertStringNotContainsString('new-password-123', json_encode($contexts));
    }

    public function test_settings_registry_and_shared_view_slots_are_extensible(): void
    {
        $this->withoutVite();
        Hooks::addFilter('jobic.admin.settings.sections', function ($sections) {
            $sections['extension'] = ['label' => '<Example>', 'description' => 'Extension settings', 'icon' => 'settings', 'url' => '/admin/extension'];

            return $sections;
        });
        Hooks::addAction('jobic.admin.content.after', fn () => Blade::render('<aside>{{ $label }}</aside>', ['label' => '<unsafe>']));
        $this->actingAs(User::factory()->create())->get(route('admin.settings.index'))->assertOk()->assertSee('&lt;Example&gt;', false)->assertSee('<aside>&lt;unsafe&gt;</aside>', false);
    }
}
