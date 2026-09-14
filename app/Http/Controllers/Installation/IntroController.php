<?php

declare(strict_types=1);

namespace App\Http\Controllers\Installation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Installation\DatabaseRequest;
use App\Http\Requests\Installation\LanguageRequest;
use App\Http\Requests\Installation\RedisRequest;
use App\Http\Requests\Installation\RunRequest;
use App\Http\Requests\Installation\StepRequest;
use App\Services\Installation\Installation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JobMetric\PackageCore\Output\Response;
use JsonException;
use Throwable;

class IntroController extends Controller
{
    public function __construct(
        private readonly Installation $installation
    ) {
    }

    /**
     * @throws JsonException
     */
    public function index(Request $request): View
    {
        return view('installation.index', $this->installation->viewData($this->context($request)));
    }

    /**
     * @throws Throwable
     */
    public function language(LanguageRequest $request): RedirectResponse
    {
        $result = $this->installation->language($request->validated(), $this->context($request));
        if (! $result->ok) {
            throw ValidationException::withMessages(['locale' => $result->message]);
        }

        return redirect()->route('installation.intro');
    }

    /**
     * @throws Throwable
     */
    public function save(StepRequest $request): JsonResponse
    {
        return $this->json($this->installation->save($request->validated(), $this->context($request)));
    }

    /**
     * @throws Throwable
     */
    public function database(DatabaseRequest $request): JsonResponse
    {
        return $this->json($this->installation->testDatabase($request->validated(), $this->context($request)));
    }

    /**
     * @throws Throwable
     */
    public function redis(RedisRequest $request): JsonResponse
    {
        return $this->json($this->installation->testRedis($request->validated(), $this->context($request)));
    }

    /**
     * @throws Throwable
     */
    public function start(RunRequest $request): JsonResponse
    {
        return $this->json($this->installation->start($request->validated(), $this->context($request)));
    }

    /**
     * @throws Throwable
     */
    public function next(RunRequest $request): JsonResponse
    {
        return $this->json($this->installation->next($request->validated(), $this->context($request)));
    }

    public function status(): JsonResponse
    {
        return $this->json(Response::make(true, '', $this->installation->progress()));
    }

    private function context(Request $request): array
    {
        if (! $request->session()->has('installation_owner')) {
            $request->session()->put('installation_owner', Str::random(64));
        }

        return [
            'owner' => $request->session()->get('installation_owner'),
            'ip'    => $request->ip(),
            'url'   => $request->getSchemeAndHttpHost() . $request->getBaseUrl(),
        ];
    }

    private function json(Response $result): JsonResponse
    {
        return response()->json($result, $result->status);
    }
}
