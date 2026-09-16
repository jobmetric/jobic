<?php

namespace App\Http\Controllers\Vuero\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Handle starter page requests.
 */
class StarterController extends Controller
{
    /**
     * Render the dashboard page.
     *
     * @param string $starter
     *
     * @return View
     */
    public function __invoke(string $starter): View
    {
        $page = collect(config('vuero.starters'))
            ->flatMap(fn (array $group, string $family): array => collect($group['pages'])
                ->map(fn (array $page, string $slug): array => [
                    ...$page,
                    'slug' => $slug,
                    'family' => $family,
                    'familyLabel' => $group['label'],
                ])->all())
            ->firstWhere('slug', $starter);

        abort_unless($page, 404);

        return view('vuero.admin.starters.show', [
            'starter' => $page,
            'starterScript' => 'resources/js/vuero/pages/admin/starter.js',
        ]);
    }
}
