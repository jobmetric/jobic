<?php

namespace App\Http\Controllers\Vuero\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Handle dashboard page requests.
 */
class DashboardController extends Controller
{
    /**
     * Render the dashboard page.
     *
     * @return View
     */
    public function __invoke(): View
    {
        return $this->show('analytics');
    }

    /**
     * Render the requested page.
     *
     * @param string $dashboard
     *
     * @return View
     */
    public function show(string $dashboard): View
    {
        $pages = collect(config('vuero.dashboards.groups'))->flatMap(fn (
                array $group,
                string $groupKey
            ): array => collect($group['pages'])->map(fn (string $title, string $slug): array => [
                    'slug'       => $slug,
                    'title'      => $title,
                    'group'      => $groupKey,
                    'groupLabel' => $group['label'],
                ])->all())->keyBy('slug');

        abort_unless($pages->has($dashboard), 404);

        $page = $pages->get($dashboard);
        $view = "vuero.admin.dashboards.{$page['group']}.{$page['slug']}";

        abort_unless(view()->exists($view), 404);

        return view($view, [
            'dashboard'       => $page,
            'dashboardScript' => "resources/js/vuero/pages/admin/dashboards/{$dashboard}.js",
        ]);
    }
}
