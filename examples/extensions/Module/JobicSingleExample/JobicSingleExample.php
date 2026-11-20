<?php

declare(strict_types=1);

namespace App\Extensions\Module\JobicSingleExample;

use App\Facades\Hooks;
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
class JobicSingleExample extends AbstractExtension
{
    /**
     * Register only this extension's own views and translation namespace.
     *
     * @param  ExtensionCore  $extension
     *
     * @return void
     * @throws \Throwable
     */
    public function configuration(ExtensionCore $extension): void
    {
        $extension->hasView()->hasTranslation();
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
            $tab->id('example')->label('extension_module_jobic_single_example::example.title')
                ->customField(function (CustomFieldBuilder $field): void {
                    $field::text()->name('title')->label('extension_module_jobic_single_example::example.card_title')->validation('required|string|max:100');
                })->customField(function (CustomFieldBuilder $field): void {
                    $field::text()->name('message')->label('extension_module_jobic_single_example::example.message')->validation('nullable|string|max:500');
                })->customField(function (CustomFieldBuilder $field): void {
                    $field::password()->name('api_secret')->label('extension_module_jobic_single_example::example.secret')->validation('nullable|string|min:8');
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
        return view('extension_module_jobic_single_example::card', ['title' => (string) ($options['title'] ?? trans('extension_module_jobic_single_example::example.default_title')), 'message' => (string) ($options['message'] ?? '')])->render();
    }

    /**
     * Observe safe instance outcomes through lifecycle hooks while active.
     *
     * @param  ExtensionCore  $core
     * @param  Application  $app
     *
     * @return void
     * @throws \Throwable
     */
    public function afterBootExtension(ExtensionCore $core, Application $app): void
    {
        Hooks::addAction('jobic.extensions.plugins.saved', static function (array $context): void {
            $id = Extension::whereNamespace(self::class)->value('id');
            if (app(Runtime::class)->active(self::class) && $id && (int) ($context['extension_id'] ?? 0) === (int) $id) {
                DB::table('jobic_example_single_audit')->insert(['plugin_id' => $context['id'], 'operation' => $context['operation'], 'user_id' => $context['user_id'], 'created_at' => now()]);
            }
        });
    }
}
