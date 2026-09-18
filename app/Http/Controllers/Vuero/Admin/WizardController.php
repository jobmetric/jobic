<?php

namespace App\Http\Controllers\Vuero\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Handle wizard page requests.
 */
class WizardController extends Controller
{
    /**
     * Render the dashboard page.
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('vuero.admin.wizard.index', [
            'steps'     => config('vuero.wizard.steps'),
            'customers' => config('vuero.wizard.customers'),
            'teammates' => config('vuero.wizard.teammates'),
            'tools'     => config('vuero.wizard.tools'),
        ]);
    }
}
