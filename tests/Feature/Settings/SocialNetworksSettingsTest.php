<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Services\Settings\SocialNetworks;
use App\Support\SocialNetworks\SocialNetworkRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use InvalidArgumentException;
use JobMetric\Rolix\Facades\Permission;
use JobMetric\Rolix\Models\Role;
use JobMetric\Setting\Facades\Setting;
use Tests\TestCase;

/**
 * Verify the generated social-network settings experience.
 */
class SocialNetworksSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Display the form in the settings hub and persist common and custom networks.
     */
    public function test_social_network_form_is_visible_and_persists_links(): void
    {
        $user = User::factory()->create();
        $url = route('admin.settings.forms.edit', ['jobic', 'social_networks']);

        $this->actingAs($user)->get(route('admin.settings.index'))
            ->assertOk()->assertSee($url, false)->assertSee(__('settings/social_networks.title'));
        $this->get($url)->assertOk()
            ->assertSee('class="cms-settings-section"', false)
            ->assertSee(__('settings/social_networks.profiles_group'))
            ->assertSee('class="cms-settings-fields cms-dynamic-fields"', false)
            ->assertSee('data-social-network-add', false)
            ->assertSee('name="networks[0][type]"', false)
            ->assertSee('https://t.me/{slug}', false)
            ->assertSee(Vite::asset('resources/css/settings/social-networks.css', 'vuero/build'), false)
            ->assertSee(Vite::asset('resources/js/settings/social-networks.js', 'vuero/build'), false);

        $this->putJson($url, [
            'networks' => [
                ['type' => 'instagram', 'slug' => 'jobmetric'],
                ['type' => 'telegram', 'slug' => 'jobmetric_support'],
            ],
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertSame('instagram', Setting::get('jobic_social_networks_networks')[0]['type']);
        $this->assertSame('https://t.me/jobmetric_support', app(SocialNetworks::class)->configured()[1]['url']);
        $this->assertSame('brand-telegram', app(SocialNetworks::class)->configured()[1]['icon']);
        $this->get($url)->assertOk()->assertSee('https://t.me/jobmetric_support');
    }

    /**
     * Reject insecure or incomplete custom network addresses.
     */
    public function test_social_network_links_require_valid_https_urls(): void
    {
        $this->actingAs(User::factory()->create())->putJson(
            route('admin.settings.forms.update', ['jobic', 'social_networks']),
            ['networks' => [['type' => 'telegram', 'slug' => 'https://t.me/unsafe']]]
        )->assertUnprocessable()->assertJsonValidationErrors(['networks.0.slug']);
    }

    /**
     * Place the dynamic permission below settings and honor a parent denial.
     */
    public function test_social_settings_permission_is_a_settings_child_and_parent_denial_wins(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['type' => 'system', 'name' => 'Denied settings parent',
            'allow' => ['admin.settings.social_networks'], 'deny' => ['admin.settings'], 'is_super' => false,
            'is_default' => false, 'ordering' => 1]);
        $user->assignRole($role);

        $this->actingAs($user)->get(route('admin.settings.forms.edit', ['jobic', 'social_networks']))->assertForbidden();
        $tree = Permission::getPermissionTreeForType('system');
        $flatten = function (array $nodes) use (&$flatten): array {
            return collect($nodes)->flatMap(fn (array $node): array => [$node['perm'], ...$flatten($node['children'])])->all();
        };
        $permissions = $flatten($tree);
        $this->assertContains('admin.settings.social_networks', $permissions);
        $this->assertNotContains('admin.settings.jobic', $permissions);
    }

    /**
     * Allow extensions to register canonical icons and HTTPS URL templates.
     */
    public function test_social_network_registry_is_extensible_and_rejects_unsafe_templates(): void
    {
        $registry = app(SocialNetworkRegistry::class);
        $registry->register('mastodon', 'Mastodon', 'brand-mastodon', 'https://social.example/@{slug}');

        $this->assertSame('https://social.example/@jobmetric', $registry->url('mastodon', 'jobmetric'));
        $this->expectException(InvalidArgumentException::class);
        $registry->register('unsafe', 'Unsafe', 'link', 'http://example.com/{slug}');
    }
}
