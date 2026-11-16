<?php

declare(strict_types=1);

namespace App\Extensions\Component\JobicSystemExample\Http\Controllers;

use App\Extensions\Component\JobicSystemExample\Http\Requests\ExampleRequest;
use App\Extensions\Component\JobicSystemExample\Services\Example;
use App\Http\Controllers\Controller;
use App\Support\Page;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\View;

/**
 * Present the example through the common CMS layout and package response contract.
 */
class ExampleController extends Controller
{
    /**
     * Render bounded public instance previews and a real extension-owned audit count.
     *
     * @param  ExampleRequest  $request
     * @param  Example  $example
     *
     * @return View
     * @throws \Throwable
     */
    public function index(ExampleRequest $request, Example $example): View
    {
        Page::prepare('extension-example');
        Page::addStyle(Vite::asset('resources/css/crud.css', 'vuero/build'));
        Page::addLocalize('extensionExample', trans('extension_component_jobic_system_example::example'));

        return view('extension_component_jobic_system_example::index', $example->page()->data);
    }
}
