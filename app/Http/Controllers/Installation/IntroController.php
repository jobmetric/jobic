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
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JobMetric\PackageCore\Output\Response;
use JsonException;
use Throwable;

/**
 * Handle intro page requests.
 */
class IntroController extends Controller
{
    /**
     * Initialize the service dependencies.
     *
     * @param Installation $installation
     *
     * @return void
     */
    public function __construct(
        private readonly Installation $installation
    ) {}

    /**
     * Render the installation wizard with saved progress.
     *
     * @param Request $request
     *
     * @return View
     * @throws JsonException
     */
    public function index(Request $request): View
    {
        $data = $this->installation->viewData($this->context($request));
        Page::prepare('installation');

        return view('installation.index', $data);
    }

    /**
     * Save the installation language.
     *
     * @param LanguageRequest $request
     *
     * @return RedirectResponse
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
     * Save the submitted installation step.
     *
     * @param StepRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function save(StepRequest $request): JsonResponse
    {
        return $this->json($this->installation->save($request->validated(), $this->context($request)));
    }

    /**
     * Test the submitted database connection settings.
     *
     * @param DatabaseRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function database(DatabaseRequest $request): JsonResponse
    {
        return $this->json($this->installation->testDatabase($request->validated(), $this->context($request)));
    }

    /**
     * Test the submitted Redis connection settings.
     *
     * @param RedisRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function redis(RedisRequest $request): JsonResponse
    {
        return $this->json($this->installation->testRedis($request->validated(), $this->context($request)));
    }

    /**
     * Start the installation task sequence.
     *
     * @param RunRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function start(RunRequest $request): JsonResponse
    {
        return $this->json($this->installation->start($request->validated(), $this->context($request)));
    }

    /**
     * Execute the next pending installation task.
     *
     * @param RunRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function next(RunRequest $request): JsonResponse
    {
        return $this->json($this->installation->next($request->validated(), $this->context($request)));
    }

    /**
     * Return the current installation progress.
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        return $this->json(Response::make(true, '', $this->installation->progress()));
    }

    /**
     * Build the installation context from the current request.
     *
     * @param Request $request
     *
     * @return array<string, mixed>
     */
    private function context(Request $request): array
    {
        if (! $request->session()->has('installation_owner')) {
            $request->session()->put('installation_owner', Str::random(64));
        }

        return [
            'owner' => $request->session()->get('installation_owner'),
            'ip' => $request->ip(),
            'url' => $request->getSchemeAndHttpHost().$request->getBaseUrl(),
        ];
    }

    /**
     * Convert the installation result into a JSON response.
     *
     * @param Response $result
     *
     * @return JsonResponse
     */
    private function json(Response $result): JsonResponse
    {
        return response()->json($result, $result->status);
    }
}
