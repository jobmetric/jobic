<?php

declare(strict_types=1);

namespace App\Settings;

use Illuminate\Support\Facades\Vite;
use JobMetric\CustomField\CustomFieldBuilder;
use JobMetric\Form\FormBuilder;
use JobMetric\Form\Group\GroupBuilder;
use JobMetric\Form\Tab\TabBuilder;
use JobMetric\Setting\Contracts\AbstractSetting;
use Throwable;

/**
 * Define the website social-network links stored in the settings table.
 */
class SocialNetworksSetting extends AbstractSetting
{
    /**
     * Return the owning application slug.
     *
     * @return string
     */
    public function application(): string
    {
        return 'jobic';
    }

    /**
     * Return the stable route and storage key.
     *
     * @return string
     */
    public function key(): string
    {
        return 'social_networks';
    }

    /**
     * Return the translated page title key.
     *
     * @return string
     */
    public function title(): string
    {
        return 'settings/social_networks.title';
    }

    /**
     * Return the translated page description key.
     *
     * @return string
     */
    public function description(): string
    {
        return 'settings/social_networks.description';
    }

    /**
     * Return the icon used by settings navigation and page headers.
     *
     * @return string
     */
    public function icon(): string
    {
        return 'share';
    }

    /**
     * Return the direct child permission used below the settings node.
     *
     * @return string
     */
    public function permission(): string
    {
        return 'admin.settings.social_networks';
    }

    /**
     * Return the page-only stylesheet managed by Domi for this driver.
     *
     * @return list<array{href: string}>
     */
    public function domiStyles(): array
    {
        return [
            [
                'href' => Vite::asset('resources/css/settings/social-networks.css', 'vuero/build'),
            ],
        ];
    }

    /**
     * Return the page-only module script managed by Domi for this driver.
     *
     * @return list<array{src: string, type: string, defer: bool}>
     */
    public function domiScripts(): array
    {
        return [
            [
                'src'   => Vite::asset('resources/js/settings/social-networks.js', 'vuero/build'),
                'type'  => 'module',
                'defer' => true,
            ],
        ];
    }

    /**
     * Return nested validation rules for repeatable social-profile rows.
     *
     * @return array<string, mixed>
     */
    public function validationRules(): array
    {
        return [
            'networks.*.type' => [
                'required',
                'string',
                'distinct',
                'max:50',
            ],
            'networks.*.slug' => [
                'required',
                'string',
                'max:190',
                'regex:/^[A-Za-z0-9._-]+$/',
            ],
        ];
    }

    /**
     * Build the repeatable social-profile settings field.
     *
     * @return FormBuilder
     * @throws Throwable
     */
    public function form(): FormBuilder
    {
        return (new FormBuilder)->tab(function (TabBuilder $tab): void {
            $tab->id('networks')->label('settings/social_networks.tab')->selected()->group(function (GroupBuilder $group
            ): void {
                $group->label('settings/social_networks.profiles_group')
                    ->description('settings/social_networks.profiles_hint')
                    ->customField(function (CustomFieldBuilder $field): void {
                        $field::socialNetworks()
                            ->name('networks')
                            ->label('settings/social_networks.profiles_group')
                            ->validation('nullable|array|max:30');
                    });
            });
        });
    }
}
