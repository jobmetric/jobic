<?php

declare(strict_types=1);

namespace App\View\Components\Layout;

use App\Support\Layouts\SiteLayoutRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Render a named position from the active website Theme layout.
 */
class Position extends Component
{
    /**
     * Create the position component.
     *
     * @param string $name
     * @param string $pageType
     *
     * @return void
     */
    public function __construct(public string $name, public string $pageType) {}

    /**
     * Render the trusted Module output for the requested position.
     *
     * @return View
     */
    public function render(): View
    {
        return view('components.layout.position', [
            'html' => app(SiteLayoutRenderer::class)->render($this->name, $this->pageType),
        ]);
    }
}
