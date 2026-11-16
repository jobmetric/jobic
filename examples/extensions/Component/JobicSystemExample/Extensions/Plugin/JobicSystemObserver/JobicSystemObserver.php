<?php

declare(strict_types=1);

namespace App\Extensions\Component\JobicSystemExample\Extensions\Plugin\JobicSystemObserver;

use App\Extensions\Component\JobicSystemExample\Extensions\Module\JobicSystemCards\JobicSystemCards;
use App\Facades\Hooks;
use App\Support\Extensions\Runtime;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use JobMetric\Extension\Contracts\AbstractExtension;
use JobMetric\Extension\Kernel\ExtensionCore;
use JobMetric\Extension\Models\Extension;
use JobMetric\Form\FormBuilder;

/**
 * Demonstrate a separately managed child whose files belong to the system component.
 */
class JobicSystemObserver extends AbstractExtension
{
    /**
     * Keep this child's resources scoped to its own extension lifecycle.
     *
     * @param  ExtensionCore  $extension
     *
     * @return void
     */
    public function configuration(ExtensionCore $extension): void {}

    /**
     * Provide an empty configuration form for the ownership example.
     *
     * @return FormBuilder
     */
    public function form(): FormBuilder
    {
        return new FormBuilder;
    }

    /**
     * Return escaped public text when this child is explicitly executed.
     *
     * @param  array<string, mixed>  $options
     *
     * @return string|null
     */
    public function handle(array $options = []): ?string
    {
        return e(static::title());
    }

    /**
     * Observe the owned module's successful instance changes only while this plugin is enabled.
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
            if (! app(Runtime::class)->active(self::class)) {
                return;
            }
            $id = Extension::whereNamespace(JobicSystemCards::class)->value('id');
            if ($id && (int) $context['extension_id'] === (int) $id) {
                DB::table('jobic_example_system_audit')->insert([
                    'plugin_id' => $context['id'], 'operation' => $context['operation'],
                    'user_id' => $context['user_id'], 'created_at' => now(),
                ]);
            }
        });
    }
}
