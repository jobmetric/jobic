<?php

declare(strict_types=1);

namespace App\Services\Extensions;

use App\Facades\Hooks;
use App\Http\Requests\Extensions\BulkRequest;
use App\Http\Requests\Extensions\CommandRequest;
use App\Http\Requests\Extensions\ExampleRequest;
use App\Http\Requests\Extensions\DiscardArchiveRequest;
use App\Http\Requests\Extensions\PluginRequest;
use App\Http\Requests\Extensions\TableRequest;
use App\Services\History\RecordHistory;
use App\Support\Extensions\Archive;
use App\Support\Extensions\Runtime;
use App\Support\LocalizedDate;
use App\Support\RecordVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use JobMetric\Extension\Exceptions\ExtensionOperationConflictException;
use JobMetric\Extension\Facades\Extension as PackageExtension;
use JobMetric\Extension\Facades\ExtensionRegistry;
use JobMetric\Extension\Facades\ExtensionTypeRegistry;
use JobMetric\Extension\Facades\InstalledExtensionsFile;
use JobMetric\Extension\Facades\Plugin;
use JobMetric\Extension\Http\Requests\StorePluginRequest;
use JobMetric\Extension\Http\Requests\UpdatePluginRequest;
use JobMetric\Extension\Http\Resources\ExtensionResource;
use JobMetric\Extension\Kernel\ExtensionKernel;
use JobMetric\Extension\Models\Extension;
use JobMetric\Extension\Models\Plugin as PluginModel;
use JobMetric\Form\Support\IOForm;
use JobMetric\PackageCore\Output\Response;
use JobMetric\PackageCore\Services\AbstractCrudService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

/**
 * Coordinate CMS lifecycle policy while delegating extension persistence to the package.
 */
class Extensions extends AbstractCrudService
{
    protected static string $modelClass = Extension::class;

    protected static string $resourceClass = ExtensionResource::class;

    /**
     * Build a bounded table from discovered identities and installed package records.
     *
     * @param  array<string, mixed>  $input
     *
     * @return Response
     * @throws Throwable
     */
    public function table(array $input): Response
    {
        $input = dto($input, TableRequest::class);
        $installed = Extension::withCount('plugins')->get()->keyBy('namespace');
        $names = array_unique(array_merge(ExtensionRegistry::values(), $installed->keys()->all()));
        $rows = [];
        foreach ($names as $namespace) {
            $record = $installed->get($namespace);
            $info = $this->metadata($namespace, $record);
            $missing = ! ExtensionRegistry::has($namespace);
            $rows[] = [
                'id' => hash('sha256', $namespace), 'namespace' => $namespace,
                'name' => trans($info['title'] ?? $record?->name ?? class_basename($namespace)),
                'type' => $info['extension'] ?? $record?->extension,
                'type_label' => trans(ExtensionTypeRegistry::get($info['extension'] ?? $record?->extension ?? '')['label'] ?? $info['extension'] ?? $record?->extension ?? ''),
                'version' => $info['version'] ?? '', 'installed_version' => $record?->info['version'] ?? null,
                'status' => $missing ? 'missing' : (! $record ? 'available' : (app(Runtime::class)->active($namespace) ? 'active' : 'inactive')),
                'depends' => $info['depends'] ?? [], 'description' => trans($info['description'] ?? ''),
                'plugins_count' => $record?->plugins_count ?? 0,
                'installed' => $record !== null, 'local' => $this->local($namespace), 'owner' => $this->owner($namespace, $record),
                '_version' => $this->version($namespace),
                'plugins_url' => $record ? route('admin.settings.extensions.plugins', $record->id) : null,
            ];
        }
        $total = count($rows);
        $stats = [$total, count(array_filter($rows, fn ($row) => $row['status'] === 'active')), $installed->count(), count(array_filter($rows, fn ($row) => $row['status'] === 'available'))];
        $search = mb_strtolower($input['search']['value'] ?? '');
        $rows = array_values(array_filter($rows, fn ($row) => ($search === '' || str_contains(mb_strtolower($row['name'].' '.$row['description']), $search))
            && (empty($input['type']) || $row['type'] === $input['type'])
            && (empty($input['status']) || $row['status'] === $input['status'])));
        $column = [1 => 'name', 2 => 'type', 3 => 'version', 4 => 'status', 5 => 'plugins_count'][(int) ($input['order'][0]['column'] ?? 1)];
        usort($rows, fn ($a, $b) => (($a[$column] <=> $b[$column]) ?: strcmp($a['namespace'], $b['namespace'])) * (($input['order'][0]['dir'] ?? 'asc') === 'desc' ? -1 : 1));

        return Response::make(true, '', ['draw' => (int) $input['draw'], 'recordsTotal' => $total,
            'recordsFiltered' => count($rows), 'stats' => $stats,
            'data' => array_slice($rows, (int) $input['start'], (int) $input['length'])]);
    }

    /**
     * Read manifest metadata without running extension constructors or form builders.
     *
     * @param  string  $namespace
     * @param  Extension|null  $record
     *
     * @return array<string, mixed>
     * @throws Throwable
     */
    public function metadata(string $namespace, ?Extension $record = null): array
    {
        if (ExtensionRegistry::has($namespace) && class_exists($namespace)) {
            $path = dirname((new \ReflectionClass($namespace))->getFileName()).'/extension.json';
            $data = is_file($path) ? json_decode(File::get($path), true) : null;
            if (is_array($data)) {
                if ($owner = $this->owner($namespace, $record)) {
                    $data['_owner'] = $owner;
                }

                return $data;
            }
        }

        return $record ? (array) $record->info?->getArrayCopy() : [];
    }

    /**
     * Resolve ownership from package discovery or committed installation metadata.
     *
     * @param  string  $namespace
     * @param  Extension|null  $record
     *
     * @return string|null
     */
    public function owner(string $namespace, ?Extension $record = null): ?string
    {
        $registry = ExtensionRegistry::getFacadeRoot();

        return (method_exists($registry, 'owner') ? $registry->owner($namespace) : null) ?? ($record?->info['_owner'] ?? null);
    }

    /**
     * Collect installed descendants without treating external dependencies as owned files.
     *
     * @param  string  $namespace
     *
     * @return list<string>
     * @throws Throwable
     */
    private function members(string $namespace): array
    {
        $members = [$namespace];
        $records = Extension::all();
        do {
            $before = count($members);
            foreach ($records as $record) {
                if (! in_array($record->namespace, $members, true) && in_array($this->owner($record->namespace, $record), $members, true)) {
                    $members[] = $record->namespace;
                }
            }
        } while (count($members) > $before);

        return $members;
    }

    /**
     * Bind optimistic lifecycle tokens to package data, manifest and activation revision.
     *
     * @param  string  $namespace
     *
     * @return string
     * @throws Throwable
     */
    public function version(string $namespace): string
    {
        $record = Extension::whereNamespace($namespace)->withCount('plugins')->first();

        return hash('sha256', serialize([$record?->getAttributes(), $this->metadata($namespace, $record), app(Runtime::class)->states()[$namespace] ?? null]));
    }

    /**
     * Identify application-owned extensions that may be removed through the CMS.
     *
     * @param  string  $namespace
     *
     * @return bool
     */
    public function local(string $namespace): bool
    {
        return str_starts_with($namespace, trim(app()->getNamespace(), '\\').'\\Extensions\\');
    }

    /**
     * Resolve one bundled example ZIP without accepting filesystem paths from the request.
     *
     * @param  array<string, mixed>  $input
     *
     * @return Response
     * @throws Throwable
     */
    public function exampleArchive(array $input): Response
    {
        $input = dto($input, ExampleRequest::class);
        $name = config('extensions.examples')[$input['variant']]['name'];
        $suffix = ($input['version'] ?? '1.0.0') === '1.1.0' ? '-1.1.0' : '';
        $path = base_path('examples/extensions/dist/'.$name.$suffix.'.zip');
        abort_unless(is_file($path), 404);

        return Response::make(true, '', ['path' => $path]);
    }

    /**
     * Prepare an upload form or verify a selected local upgrade target.
     *
     * @param  string  $namespace
     * @param  bool  $upgrade
     *
     * @return array{namespace: string, version: string|null}
     * @throws Throwable
     */
    public function archivePage(string $namespace, bool $upgrade): array
    {
        if (! $upgrade) {
            return ['namespace' => '', 'version' => null];
        }
        abort_unless(ExtensionRegistry::has($namespace) && $this->local($namespace) && Extension::whereNamespace($namespace)->exists(), 404);

        return ['namespace' => $namespace, 'version' => $this->version($namespace)];
    }

    /**
     * Validate and execute one authorized lifecycle action under the shared operation lock.
     *
     * @param  string  $operation
     * @param  array<string, mixed>  $input
     * @param  int  $userId
     *
     * @return Response
     * @throws Throwable
     */
    public function command(string $operation, array $input, int $userId): Response
    {
        $input = dto($input, CommandRequest::class);

        return app(Runtime::class)->locked(function () use ($operation, $input, $userId): Response {
            if (in_array($operation, ['upload', 'upgrade'], true)) {
                return $this->archive($operation, $input, $userId);
            }
            $namespace = (string) ($input['namespace'] ?? '');
            $record = Extension::whereNamespace($namespace)->first();
            abort_unless(ExtensionRegistry::has($namespace) || $record, 404);
            abort_unless(hash_equals($this->version($namespace), $input['_version'] ?? ''), 409, trans('extensions.conflict'));
            if ($this->owner($namespace, $record) && in_array($operation, ['install', 'uninstall', 'delete'], true)) {
                throw ValidationException::withMessages(['namespace' => trans('extensions.owned_extension')]);
            }
            $context = ['namespace' => $namespace, 'operation' => $operation, 'user_id' => $userId];
            if (in_array($operation, ['uninstall', 'delete'], true) && empty($input['confirm'])) {
                throw ValidationException::withMessages(['confirm' => trans('extensions.confirm_required')]);
            }
            if (in_array($operation, ['deactivate', 'uninstall', 'delete'], true)) {
                $this->dependants($namespace, $operation === 'deactivate');
            }
            if ($operation === 'activate') {
                abort_unless($record && ExtensionRegistry::has($namespace), 409);
                $this->dependencies($namespace);
            }
            if ($operation === 'install') {
                abort_if($record !== null, 409);
                $this->dependencies($namespace, false);
                $info = $this->metadata($namespace);
                if (Extension::where('extension', $info['extension'] ?? '')->where('name', $info['name'] ?? '')->whereNull('info->_owner')->exists()) {
                    throw ValidationException::withMessages(['namespace' => trans('extensions.identity_conflict')]);
                }
            }
            if ($operation === 'uninstall') {
                abort_unless($record && ! app(Runtime::class)->active($namespace), 409, trans('extensions.deactivate_first'));
            }
            if ($operation === 'delete') {
                abort_unless(! $record && $this->local($namespace), 409);
                $path = resolveNamespacePath($namespace);
                $folder = $path && is_dir($path) ? $path : dirname((string) $path);
                $root = str_replace('\\', '/', (string) realpath((string) config('extensions.root')));
                $realFolder = str_replace('\\', '/', (string) realpath($folder));
                abort_unless($root !== '' && $realFolder !== '' && str_starts_with($realFolder.'/', $root.'/'), 422, trans('extensions.invalid_archive'));
            }
            Hooks::action('jobic.extensions.lifecycle.saving', $context);
            try {
                switch ($operation) {
                    case 'install':
                        app(Runtime::class)->set($namespace, false);
                        $this->requireSuccess(PackageExtension::install($namespace));
                        foreach ($this->members($namespace) as $member) {
                            app(Runtime::class)->set($member, false);
                        }
                        break;
                    case 'activate':
                    case 'deactivate':
                        abort_unless($record !== null, 409);
                        app(Runtime::class)->set($namespace, $operation === 'activate');
                        break;
                    case 'uninstall':
                        $this->requireSuccess(PackageExtension::uninstall($namespace, true));
                        break;
                    case 'delete':
                        $this->requireSuccess(PackageExtension::delete(ExtensionRegistry::get($namespace), $namespace));
                        ExtensionRegistry::unregister($namespace);
                        break;
                    default:
                        abort(422);
                }
            } catch (ExtensionOperationConflictException) {
                abort(409, trans('extensions.conflict'));
            } catch (\InvalidArgumentException) {
                throw ValidationException::withMessages(['namespace' => trans('extensions.invalid_archive')]);
            } finally {
                InstalledExtensionsFile::syncFromDatabase(app());
                ExtensionKernel::clearDiscoverCache();
            }
            Hooks::notify('jobic.extensions.lifecycle.saved', $context);

            return Response::make(true, trans('extensions.saved'), ['version' => $this->version($namespace)]);
        });
    }

    /**
     * Reject absent, inactive or cyclic dependencies using the package's declared identities.
     *
     * @param  string  $namespace
     * @param  bool  $requireActive
     * @param  array<int, string>  $trail
     * @param  array<string, mixed>|null  $override
     *
     * @return void
     * @throws ValidationException
     */
    public function dependencies(string $namespace, bool $requireActive = true, array $trail = [], ?array $override = null): void
    {
        if (in_array($namespace, $trail, true)) {
            throw ValidationException::withMessages(['namespace' => trans('extensions.dependency_cycle')]);
        }
        $trail[] = $namespace;
        $info = $override ?? $this->metadata($namespace);
        foreach (array_merge($info['depends'] ?? [], array_filter([$info['_owner'] ?? null])) as $dependency) {
            if (! is_string($dependency) || ! ExtensionRegistry::has($dependency)
                || ! Extension::whereNamespace($dependency)->exists()
                || ($requireActive && ! app(Runtime::class)->active($dependency))) {
                throw ValidationException::withMessages(['namespace' => trans('extensions.dependency_missing', ['name' => is_string($dependency) ? class_basename($dependency) : ''])]);
            }
            $this->dependencies($dependency, $requireActive, $trail);
        }
    }

    /**
     * Protect installed dependants from losing a required extension.
     *
     * @param  string  $namespace
     * @param  bool  $activeOnly
     *
     * @return void
     * @throws ValidationException
     */
    private function dependants(string $namespace, bool $activeOnly): void
    {
        $members = $this->members($namespace);
        foreach (Extension::all() as $record) {
            if (($activeOnly && ! app(Runtime::class)->active($record->namespace))) {
                continue;
            }
            if (! in_array($record->namespace, $members, true) && array_intersect($members, $this->metadata($record->namespace, $record)['depends'] ?? [])) {
                throw ValidationException::withMessages(['namespace' => trans('extensions.required_by', ['name' => $record->name])]);
            }
        }
    }

    /**
     * Inspect an upload, remove its native ZIP and retain only the payload needed for explicit review.
     *
     * @param  string  $operation
     * @param  array<string, mixed>  $input
     * @param  int  $userId
     *
     * @return Response
     * @throws Throwable
     */
    private function archive(string $operation, array $input, int $userId): Response
    {
        $this->pruneStaging();
        $upload = $input['archive'] ?? null;
        if (! $upload) {
            throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
        }
        $runtime = app(Runtime::class);
        $ticket = bin2hex(random_bytes(32));
        $stage = $runtime->directory().'/staging/'.$ticket;
        $keep = false;
        try {
            File::ensureDirectoryExists($stage, 0700);
            $path = $this->uploadPath($upload);
            try {
                $info = app(Archive::class)->stage($path, $stage.'/payload');
            } finally {
                // Only PHP-owned HTTP uploads may be removed; fixture and deployment ZIPs are borrowed.
                if (is_uploaded_file($path)) {
                    File::delete($path);
                }
            }
            $this->archiveCandidate($operation, $info, $input, $info['staged_path']);
            $report = $info['inspection'];
            $relative = ltrim(substr($info['staged_path'], strlen($stage.'/payload')), '/\\');
            unset($info['inspection'], $info['staged_path']);
            $expires = now()->addSeconds((int) config('extensions.stage_ttl', 1800));
            File::put($stage.'/review.json', json_encode([
                'owner_id' => $userId, 'operation' => $operation, 'input' => array_intersect_key($input, array_flip(['namespace', '_version'])),
                'manifest' => $info, 'relative' => $relative, 'report' => $report,
                'digest' => hash('sha256', json_encode($report, JSON_THROW_ON_ERROR)), 'expires_at' => $expires->timestamp,
            ], JSON_THROW_ON_ERROR));
            $keep = true;

            return Response::make(true, trans('extensions.archive_ready'), [
                'phase' => 'review', 'ticket' => $ticket, 'report' => $report,
                'confirm_url' => route('admin.settings.extensions.'.$operation.'-confirm'),
                'discard_url' => route('admin.settings.extensions.'.$operation.'-discard'),
                'expires_at_display' => LocalizedDate::format($expires),
            ]);
        } finally {
            if (! $keep) {
                File::deleteDirectory($stage);
            }
        }
    }

    /**
     * Resolve readable uploaded files even when native realpath cannot resolve a temporary file.
     *
     * @param  UploadedFile  $upload
     *
     * @return string
     * @throws ValidationException
     */
    private function uploadPath(UploadedFile $upload): string
    {
        $path = $upload->getRealPath() ?: $upload->getPathname();
        if (! $upload->isValid() || ! is_file($path) || ! is_readable($path)) {
            throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
        }

        return $path;
    }

    /**
     * Revalidate an actor-owned review ticket and place only the exact files the actor confirmed.
     *
     * @param  string  $operation
     * @param  array<string, mixed>  $input
     * @param  int  $userId
     *
     * @return Response
     * @throws Throwable
     */
    public function confirmArchive(string $operation, array $input, int $userId): Response
    {
        $input = dto($input, CommandRequest::class);
        if (empty($input['ticket']) || empty($input['confirm'])) {
            throw ValidationException::withMessages(['confirm' => trans('extensions.confirm_required')]);
        }

        return app(Runtime::class)->locked(function () use ($operation, $input, $userId): Response {
            $stage = app(Runtime::class)->directory().'/staging/'.$input['ticket'];
            abort_unless(is_file($stage.'/review.json'), 410, trans('extensions.review_expired'));
            $review = json_decode(File::get($stage.'/review.json'), true);
            abort_unless(is_array($review) && ($review['owner_id'] ?? null) === $userId && ($review['operation'] ?? null) === $operation, 403);
            try {
                abort_if(($review['expires_at'] ?? 0) < now()->timestamp, 410, trans('extensions.review_expired'));
                $base = realpath($stage.'/payload/'.($review['relative'] ?? ''));
                $payload = str_replace('\\', '/', (string) realpath($stage.'/payload'));
                abort_unless($base && str_starts_with(str_replace('\\', '/', $base).'/', $payload.'/'), 422, trans('extensions.review_changed'));
                $report = app(Archive::class)->report($base, $review['manifest'], (int) $review['report']['archive_bytes']);
                abort_unless(hash_equals($review['digest'], hash('sha256', json_encode($report, JSON_THROW_ON_ERROR))), 409, trans('extensions.review_changed'));
                $candidate = $this->archiveCandidate($operation, $review['manifest'], $review['input'], $base);
                app(Archive::class)->verify($review['manifest'], $base);
                $this->promoteArchive($operation, $review['manifest'], $base, $candidate, $userId);

                return Response::make(true, trans('extensions.archive_placed'), ['phase' => 'placed', 'report' => $report,
                    'version' => $operation === 'upgrade' ? $this->version($candidate['namespace']) : null]);
            } finally {
                File::deleteDirectory($stage);
            }
        });
    }

    /**
     * Discard one actor-owned review without changing installed extensions or recovery backups.
     *
     * @param string $operation
     * @param array<string, mixed> $input
     * @param int $userId
     *
     * @return Response
     * @throws Throwable
     */
    public function discardArchive(string $operation, array $input, int $userId): Response
    {
        $input = dto($input, DiscardArchiveRequest::class);

        return app(Runtime::class)->locked(function () use ($operation, $input, $userId): Response {
            $stage = app(Runtime::class)->directory().'/staging/'.$input['ticket'];
            if (is_file($stage.'/review.json')) {
                $review = json_decode(File::get($stage.'/review.json'), true);
                abort_unless(is_array($review) && ($review['owner_id'] ?? null) === $userId && ($review['operation'] ?? null) === $operation, 403);
                File::deleteDirectory($stage);
            }

            return Response::make(true, trans('extensions.archive_discarded'));
        });
    }

    /**
     * Check identity, version, dependency and directory policy both before review and before promotion.
     *
     * @param  string  $operation
     * @param  array<string, mixed>  $info
     * @param  array<string, mixed>  $input
     * @param  string  $base
     *
     * @return array<string, mixed>
     * @throws Throwable
     */
    private function archiveCandidate(string $operation, array $info, array $input, string $base): array
    {
        $namespace = trim(app()->getNamespace(), '\\').'\\Extensions\\'.$info['extension'].'\\'.$info['name'].'\\'.$info['name'];
        $root = (string) config('extensions.root');
        $typeDirectory = $root.'/'.$info['extension'];
        if (is_dir($root) && is_dir($typeDirectory)) {
            $rootPath = str_replace('\\', '/', (string) realpath($root));
            $typePath = str_replace('\\', '/', (string) realpath($typeDirectory));
            abort_unless(str_starts_with($typePath.'/', $rootPath.'/'), 422, trans('extensions.invalid_archive'));
        }
        $target = $typeDirectory.'/'.$info['name'];
        abort_if(is_link($target), 422, trans('extensions.invalid_archive'));
        if (is_dir($target)) {
            abort_unless(str_starts_with(str_replace('\\', '/', (string) realpath($target)).'/', str_replace('\\', '/', (string) realpath($typeDirectory)).'/'), 422, trans('extensions.invalid_archive'));
        }
        $record = Extension::whereNamespace($namespace)->first();
        $active = $record && app(Runtime::class)->active($namespace);
        if ($operation === 'upgrade') {
            abort_unless($record && $namespace === ($input['namespace'] ?? '') && $this->local($namespace), 409);
            if ($this->owner($namespace, $record)) {
                throw ValidationException::withMessages(['archive' => trans('extensions.owned_extension')]);
            }
            abort_unless(hash_equals($this->version($namespace), $input['_version'] ?? ''), 409, trans('extensions.conflict'));
            if (! version_compare($info['version'], $this->metadata($namespace, $record)['version'], '>')) {
                throw ValidationException::withMessages(['archive' => trans('extensions.version_required')]);
            }
            $this->dependencies($namespace, (bool) $active, [], $info);
        } else {
            abort_if($record || file_exists($target) || ExtensionRegistry::byTypeAndName($info['extension'], $info['name']), 409, trans('extensions.identity_conflict'));
        }

        if (! empty($info['children']) || count($this->members($namespace)) > 1) {
            $plan = app(Archive::class)->composition($base, $info);
            try {
                app('Extension')->orderComposition($plan);
            } catch (\InvalidArgumentException) {
                throw ValidationException::withMessages(['archive' => trans('extensions.dependency_missing', ['name' => $info['name']])]);
            }
            if ($operation === 'upgrade') {
                $expected = $this->members($namespace);
                $proposed = array_column($plan, 'namespace');
                sort($expected);
                sort($proposed);
                if ($expected !== $proposed) {
                    throw ValidationException::withMessages(['archive' => trans('extensions.composition_membership')]);
                }
                foreach ($plan as $item) {
                    $previous = Extension::whereNamespace($item['namespace'])->firstOrFail();
                    if (version_compare($item['info']['version'], $previous->info['version'], '<')) {
                        throw ValidationException::withMessages(['archive' => trans('extensions.version_required')]);
                    }
                }
            }
        }

        return compact('namespace', 'target', 'record', 'active');
    }

    /**
     * Promote confirmed files and preserve backups and inactive failure state during migration errors.
     *
     * @param  string  $operation
     * @param  array<string, mixed>  $info
     * @param  string  $base
     * @param  array<string, mixed>  $candidate
     * @param  int  $userId
     *
     * @return void
     * @throws Throwable
     */
    private function promoteArchive(string $operation, array $info, string $base, array $candidate, int $userId): void
    {
        $runtime = app(Runtime::class);
        ['namespace' => $namespace, 'target' => $target, 'record' => $record, 'active' => $active] = $candidate;
        $context = ['namespace' => $namespace, 'operation' => $operation, 'user_id' => $userId];
        Hooks::action('jobic.extensions.lifecycle.saving', $context);
        $runtime->set($namespace, false);
        $backup = $runtime->directory().'/backups/'.bin2hex(random_bytes(16));
        File::ensureDirectoryExists(dirname($target));
        File::ensureDirectoryExists(dirname($backup));
        if (is_dir($target) && ! rename($target, $backup)) {
            throw new \RuntimeException('Unable to back up extension files.');
        }
        try {
            if (! rename($base, $target)) {
                throw new \RuntimeException('Unable to promote extension files.');
            }
            if ($record) {
                $result = Process::path(base_path())->env($runtime->workerEnvironment())->timeout(120)
                    ->run($runtime->upgradeCommand($namespace));
                if (! $result->successful()) {
                    Log::error('Extension upgrade worker failed.', ['namespace' => $namespace, 'exit_code' => $result->exitCode()]);
                    throw ValidationException::withMessages(['archive' => trans('extensions.upgrade_failed')]);
                }
                $record->refresh();
                // The isolated package worker persists composed metadata after all migrations succeed.
                if (empty($info['children'])) {
                    $record->update(['info' => $info]);
                }
                $runtime->set($namespace, (bool) $active);
            }
        } catch (Throwable $exception) {
            File::deleteDirectory($target);
            if (is_dir($backup)) {
                rename($backup, $target);
            }
            throw $exception;
        } finally {
            ExtensionKernel::clearDiscoverCache();
            InstalledExtensionsFile::syncFromDatabase(app());
        }
        Hooks::notify('jobic.extensions.lifecycle.saved', $context);
    }

    /**
     * Remove expired review tickets without touching live extensions or retained backups.
     *
     * @return Response
     * @throws Throwable
     */
    public function purgeArchives(): Response
    {
        return app(Runtime::class)->locked(fn (): Response => Response::make(true, trans('extensions.saved'), ['removed' => $this->pruneStaging()]));
    }

    /**
     * Remove expired private reviews while the caller holds the shared extension lock.
     *
     * @return int
     * @throws Throwable
     */
    private function pruneStaging(): int
    {
        $root = app(Runtime::class)->directory().'/staging';
        $removed = 0;
        foreach (is_dir($root) ? File::directories($root) : [] as $directory) {
            if (is_link($directory) || ! preg_match('/^[a-f0-9]{64}$/', basename($directory))) {
                continue;
            }
            $path = $directory.'/review.json';
            $data = is_file($path) ? json_decode(File::get($path), true) : [];
            if (($data['expires_at'] ?? filemtime($directory) + (int) config('extensions.stage_ttl', 1800)) < now()->timestamp) {
                File::deleteDirectory($directory);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Reject unsuccessful package responses before reporting a CMS operation as saved.
     *
     * @param  Response  $response
     *
     * @return void
     * @throws ValidationException
     */
    private function requireSuccess(Response $response): void
    {
        if (! $response->ok) {
            throw ValidationException::withMessages(['namespace' => $response->message]);
        }
    }

    /**
     * Query a bounded instance page without returning arbitrary plugin field values.
     *
     * @param  int  $extensionId
     * @param  array<string, mixed>  $input
     *
     * @return Response
     * @throws Throwable
     */
    public function pluginTable(int $extensionId, array $input): Response
    {
        $input = dto($input, TableRequest::class);
        $extension = Extension::findOrFail($extensionId);
        $query = $extension->plugins();
        $total = (clone $query)->count();
        $active = (clone $query)->where('status', true)->count();
        $recent = (clone $query)->where('created_at', '>=', now()->subDays(30))->count();
        if (! empty($input['search']['value'])) {
            $query->where('name', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $input['search']['value']).'%');
        }
        if (in_array($input['status'] ?? '', ['active', 'inactive'], true)) {
            $query->where('status', $input['status'] === 'active');
        }
        $count = (clone $query)->count();
        $column = [1 => 'name', 2 => 'status', 3 => 'created_at', 4 => 'updated_at', 5 => 'id'][(int) ($input['order'][0]['column'] ?? 1)];
        $records = $query->orderBy($column, $input['order'][0]['dir'] ?? 'asc')->orderBy('id')
            ->offset((int) $input['start'])->limit((int) $input['length'])->get();
        $rows = $records->map(fn (PluginModel $record): array => [
            'id' => (string) $record->id, 'name' => $record->name, 'status' => $record->status ? 'active' : 'inactive',
            'created_at_display' => LocalizedDate::format($record->created_at),
            'updated_at_display' => LocalizedDate::format($record->updated_at),
            '_version' => $this->pluginVersion($record),
            'edit_url' => route('admin.settings.extensions.plugins.edit', [$extensionId, $record->id]),
            'commands' => collect(['activate', 'deactivate', 'delete'])->mapWithKeys(fn ($operation) => [$operation => route('admin.settings.extensions.plugins.command', [$extensionId, $record->id, $operation])])->all(),
        ])->all();

        return Response::make(true, '', ['draw' => (int) $input['draw'], 'recordsTotal' => $total,
            'recordsFiltered' => $count, 'data' => $rows, 'stats' => [$total, $active, $total - $active, $recent]]);
    }

    /**
     * Apply an instance status or deletion operation using the same validated persistence path.
     *
     * @param  int  $extensionId
     * @param  int  $pluginId
     * @param  string  $operation
     * @param  array<string, mixed>  $input
     * @param  int  $userId
     *
     * @return Response
     * @throws Throwable
     */
    public function pluginCommand(int $extensionId, int $pluginId, string $operation, array $input, int $userId): Response
    {
        $record = PluginModel::where('extension_id', $extensionId)->findOrFail($pluginId);

        return $this->savePlugin($extensionId, $pluginId, $input + ['name' => $record->name, 'status' => $operation === 'activate' ? 1 : 0], $userId, $operation === 'delete');
    }

    /**
     * Return safe instance history after checking ownership.
     *
     * @param  int  $extensionId
     * @param  int  $pluginId
     * @param  int  $page
     *
     * @return Response
     * @throws Throwable
     */
    public function pluginHistory(int $extensionId, int $pluginId, int $page): Response
    {
        PluginModel::where('extension_id', $extensionId)->findOrFail($pluginId);

        return app(RecordHistory::class)->page('extension_plugins', $pluginId, $page);
    }

    /**
     * Prepare package-owned plugin fields and records for server-rendered forms.
     *
     * @param  int  $extensionId
     * @param  int|null  $pluginId
     *
     * @return array<string, mixed>
     * @throws Throwable
     */
    public function pluginPage(int $extensionId, ?int $pluginId = null): array
    {
        $extension = Extension::findOrFail($extensionId);
        $record = $pluginId ? $extension->plugins()->findOrFail($pluginId) : null;
        $driver = app($extension->namespace);

        $form = $driver->form()->build();
        $values = IOForm::toArray($form, (array) $record?->fields?->getArrayCopy());
        foreach ($form->getAllCustomFields(true) as $field) {
            if ($field->type === 'password') {
                unset($values[$field->params['name']]);
            }
        }

        return ['extension' => $extension, 'record' => $record,
            'form' => $form, 'values' => $values,
            'canCreate' => (bool) ($extension->info['multiple'] ?? false) || ! $extension->plugins()->exists(),
            'version' => $record ? $this->pluginVersion($record) : null];
    }

    /**
     * Hash stored plugin attributes without exposing their field values.
     *
     * @param  PluginModel  $plugin
     *
     * @return string
     * @throws Throwable
     */
    public function pluginVersion(PluginModel $plugin): string
    {
        return hash_hmac('sha256', RecordVersion::token($plugin, 'extension_plugins'), (string) config('app.key'));
    }

    /**
     * Validate dynamic fields before CMS hooks and delegate instance persistence to the package.
     *
     * @param  int  $extensionId
     * @param  int|null  $pluginId
     * @param  array<string, mixed>  $input
     * @param  int  $userId
     * @param  bool  $delete
     *
     * @return Response
     * @throws Throwable
     */
    public function savePlugin(int $extensionId, ?int $pluginId, array $input, int $userId, bool $delete = false): Response
    {
        return app(Runtime::class)->locked(fn (): Response => DB::transaction(fn (): Response => $this->persistPlugin($extensionId, $pluginId, $input, $userId, $delete)));
    }

    /**
     * Persist one validated instance inside the caller's operation lock and transaction.
     *
     * @param  int  $extensionId
     * @param  int|null  $pluginId
     * @param  array<string, mixed>  $input
     * @param  int  $userId
     * @param  bool  $delete
     *
     * @return Response
     * @throws Throwable
     */
    private function persistPlugin(int $extensionId, ?int $pluginId, array $input, int $userId, bool $delete): Response
    {
        $extension = Extension::lockForUpdate()->findOrFail($extensionId);
        $record = $pluginId ? $extension->plugins()->lockForUpdate()->findOrFail($pluginId) : null;
        if ($record) {
            abort_unless(hash_equals($this->pluginVersion($record), $input['_version'] ?? ''), 409, trans('extensions.conflict'));
        }
        if ($delete) {
            abort_unless($record && ! empty($input['confirm']), 422);
        } else {
            $input = dto($input, PluginRequest::class);
            if ($record && array_key_exists('fields', $input)) {
                $form = app($extension->namespace)->form()->build();
                foreach ($form->getAllCustomFields(true) as $field) {
                    $name = $field->params['name'];
                    if ($field->type === 'password' && in_array($input['fields'][$name] ?? null, ['', null], true)) {
                        unset($input['fields'][$name]);
                    }
                }
                $input['fields'] = array_replace((array) $record->fields?->getArrayCopy(), $input['fields']);
            }
            $input = dto($input, $record ? UpdatePluginRequest::class : StorePluginRequest::class,
                ['extension_id' => $extensionId, 'plugin' => $record]);
        }
        if (! $record && ! $delete && ! ($extension->info['multiple'] ?? false) && $extension->plugins()->exists()) {
            throw ValidationException::withMessages(['name' => trans('extensions.single_instance')]);
        }
        $before = $record ? ['name' => $record->name, 'status' => $record->status] : [];
        $context = ['extension_id' => $extensionId, 'id' => $pluginId, 'operation' => $delete ? 'delete' : ($record ? 'update' : 'create'), 'user_id' => $userId];
        Hooks::action('jobic.extensions.plugins.saving', $context);
        $response = $delete ? Plugin::destroy($record->id) : ($record
            ? Plugin::updateForExtension($extensionId, $record->id, $input)
            : Plugin::storeForExtension($extensionId, $input));
        $this->requireSuccess($response);
        $saved = $delete ? null : ($record ? $record->fresh() : $extension->plugins()->latest('id')->first());
        app(RecordHistory::class)->record('extension_plugins', (int) ($saved?->id ?? $pluginId), $userId, $context['operation'], $before,
            $saved ? ['name' => $saved->name, 'status' => $saved->status] : []);
        DB::afterCommit(fn () => Hooks::notify('jobic.extensions.plugins.saved', $context));

        return Response::make(true, trans('extensions.saved'), ['id' => $saved?->id,
            'version' => $saved ? $this->pluginVersion($saved) : null,
            'save_url' => $saved ? route('admin.settings.extensions.plugins.update', [$extensionId, $saved->id]) : null,
            'edit_url' => $saved ? route('admin.settings.extensions.plugins.edit', [$extensionId, $saved->id]) : null]);
    }

    /**
     * Apply one confirmed plugin selection atomically, rejecting every stale or foreign ID before hooks.
     *
     * @param  int  $extensionId
     * @param  array<string, mixed>  $input
     * @param  int  $userId
     *
     * @return Response
     * @throws Throwable
     */
    public function bulkPlugins(int $extensionId, array $input, int $userId): Response
    {
        $input = dto($input, BulkRequest::class);

        return app(Runtime::class)->locked(function () use ($extensionId, $input, $userId): Response {
            return DB::transaction(function () use ($extensionId, $input, $userId): Response {
                $extension = Extension::lockForUpdate()->findOrFail($extensionId);
                $records = $extension->plugins()->whereIn('id', array_column($input['entries'], 'id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                abort_unless($records->count() === count($input['entries']), 409, trans('extensions.conflict'));
                foreach ($input['entries'] as $entry) {
                    abort_unless(hash_equals($this->pluginVersion($records->get($entry['id'])), $entry['_version']), 409, trans('extensions.conflict'));
                }
                foreach ($input['entries'] as $entry) {
                    $record = $records->get($entry['id']);
                    $this->persistPlugin($extensionId, $record->id, ['name' => $record->name, 'status' => $input['operation'] === 'activate' ? 1 : 0,
                        '_version' => $entry['_version'], 'confirm' => 1], $userId, $input['operation'] === 'delete');
                }

                return Response::make(true, trans('extensions.saved'));
            });
        });
    }
}
