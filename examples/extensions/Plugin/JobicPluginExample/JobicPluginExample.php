<?php

declare(strict_types=1);

namespace App\Extensions\Plugin\JobicPluginExample;

use App\Facades\Hooks;
use App\Facades\Permissions;
use App\Support\Extensions\Runtime;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use JobMetric\CustomField\CustomFieldBuilder;
use JobMetric\Extension\Contracts\AbstractExtension;
use JobMetric\Extension\Kernel\ExtensionCore;
use JobMetric\Extension\Models\Extension;
use JobMetric\Form\FormBuilder;
use JobMetric\Form\Tab\TabBuilder;

/**
 * Demonstrate a removable single extension using the existing CMS contracts.
 */
class JobicPluginExample extends AbstractExtension
{
    /**
     * Register only this extension's own views, routes and translation namespace.
     *
     * @param  ExtensionCore  $extension
     *
     * @return void
     * @throws \Throwable
     */
    public function configuration(ExtensionCore $extension): void
    {
        $extension->hasView()->hasTranslation()->hasRoute();
    }

    /**
     * Define the package-owned instance form, including a write-only credential example.
     *
     * @return FormBuilder
     * @throws \Throwable
     */
    public function form(): FormBuilder
    {
        return (new FormBuilder)->tab(function (TabBuilder $tab): void {
            $tab->id('example')->label('extension_plugin_jobic_plugin_example::example.title')
                ->customField(function (CustomFieldBuilder $field): void {
                    $field::text()->name('title')->label('extension_plugin_jobic_plugin_example::example.card_title')->validation('required|string|max:100');
                })->customField(function (CustomFieldBuilder $field): void {
                    $field::text()->name('message')->label('extension_plugin_jobic_plugin_example::example.message')->validation('nullable|string|max:500');
                })->customField(function (CustomFieldBuilder $field): void {
                    $field::password()->name('api_secret')->label('extension_plugin_jobic_plugin_example::example.secret')->validation('nullable|string|min:8');
                });
        });
    }

    /**
     * Render escaped public instance content without revealing credential fields.
     *
     * @param  array<string, mixed>  $options
     *
     * @return string|null
     * @throws \Throwable
     */
    public function handle(array $options = []): ?string
    {
        return view('extension_plugin_jobic_plugin_example::card', ['title' => (string) ($options['title'] ?? trans('extension_plugin_jobic_plugin_example::example.default_title')), 'message' => (string) ($options['message'] ?? '')])->render();
    }

    /**
     * Add an authorized settings card and observe safe instance outcomes while active.
     *
     * @param  ExtensionCore  $core
     * @param  Application  $app
     *
     * @return void
     * @throws \Throwable
     */
    public function afterBootExtension(ExtensionCore $core, Application $app): void
    {
        Permissions::addPermission('admin.settings.extensions.examples', 'extensions.example_archives');
        Permissions::addPermission('admin.settings.extensions.examples.plugin', 'extension_plugin_jobic_plugin_example::example.title', 'admin.extension-examples.plugin', order: 180);
        Hooks::addView('jobic.admin.content.after', 'extension_plugin_jobic_plugin_example::notice');
        Hooks::addAction('jobic.extensions.plugins.saved', static function (array $context): void {
            $id = Extension::whereNamespace(self::class)->value('id');
            if (app(Runtime::class)->active(self::class) && $id && (int) ($context['extension_id'] ?? 0) === (int) $id) {
                DB::table('jobic_example_plugin_audit')->insert(['plugin_id' => $context['id'], 'operation' => $context['operation'], 'user_id' => $context['user_id'], 'created_at' => now()]);
            }
        });
    }
}
