<?php

declare(strict_types=1);

namespace App\Services\Languages;

use App\Facades\Hooks;
use App\Http\Requests\Languages\BulkRequest;
use App\Http\Requests\Languages\HistoryRequest;
use App\Http\Requests\Languages\LanguageRequest;
use App\Http\Requests\Languages\TableRequest;
use App\Services\History\RecordHistory;
use App\Support\LocalizedDate;
use App\Support\RecordVersion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JobMetric\Language\Facades\Language as LanguageService;
use JobMetric\Language\Http\Resources\LanguageResource;
use JobMetric\Language\Models\Language;
use JobMetric\PackageCore\Output\Response;
use JobMetric\PackageCore\Services\AbstractCrudService;
use Throwable;

/**
 * Manage the package language registry through validated CMS operations.
 */
class Languages extends AbstractCrudService
{
    protected static string $modelClass = Language::class;

    protected static string $resourceClass = LanguageResource::class;

    /**
     * Read a bounded, sorted table page with unfiltered registry statistics.
     *
     * @param array<string, mixed> $input
     *
     * @throws ValidationException
     * @throws Throwable
     */
    public function table(array $input): Response
    {
        $data = dto($input, TableRequest::class);

        $query = Language::query();
        $total = (clone $query)->count();
        $stats = [
            $total,
            (clone $query)->where('status', true)
                ->count(),
            (clone $query)->where('direction', 'rtl')
                ->count(),
            (clone $query)->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        foreach (['status', 'direction'] as $filter) {
            if (isset($data[$filter]) && $data[$filter] !== '') {
                $query->where($filter, $data[$filter]);
            }
        }

        if ($term = ($data['search']['value'] ?? null)) {
            $query->where(fn ($q) => $q->where('name', 'like', '%' . $term . '%')
                ->orWhere('locale', 'like', '%' . $term . '%'));
        }

        $filtered = (clone $query)->count();
        $columns = [
            1 => 'name',
            2 => 'locale',
            3 => 'direction',
            4 => 'status',
            5 => 'created_at',
        ];
        $query->orderBy($columns[$data['order'][0]['column'] ?? 1], $data['order'][0]['dir'] ?? 'asc')
            ->orderBy('id');

        return Response::make(true, '', [
            'draw'            => (int) $data['draw'],
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $query->offset((int) $data['start'])
                ->limit((int) $data['length'])
                ->get()
                ->map(function (Language $language): array {
                    $item = (new LanguageResource($language))->resolve();
                    $item['created_at_display'] = LocalizedDate::format($language->created_at, false);

                    return $item;
                }),
            'stats'           => $stats,
        ]);
    }

    /**
     * Find a registry record for editing or read-only presentation.
     *
     *
     * @throws ModelNotFoundException
     */
    public function record(int $id): Language
    {
        return Language::query()
            ->findOrFail($id);
    }

    /**
     * Validate and persist one language, notifying extensions after commit.
     *
     * @param array<string, mixed> $input
     *
     * @throws Throwable
     */
    public function saveRecord(array $input, ?int $id, int $userId): Response
    {
        $data = dto($input, LanguageRequest::class);
        $context = [
            'user_id'   => $userId,
            'id'        => $id,
            'operation' => $id ? 'update' : 'create',
        ];

        $version = null;
        $record = DB::transaction(function () use ($data, $id, $context, &$version) {
            $all = Language::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $record = $id ? $all->firstWhere('id', $id) : new Language;

            if (! $record) {
                throw (new ModelNotFoundException)->setModel(Language::class, [$id]);
            }

            if ($id && ! hash_equals(RecordVersion::token($record, 'languages'), (string) ($data['_version'] ?? ''))) {
                return Response::make(false, __('languages.conflict'), null, 409);
            }
            unset($data['_version']);

            if (! $data['status'] && $all->where('status', true)
                    ->where('id', '!=', $id)
                    ->isEmpty()) {
                throw ValidationException::withMessages(['status' => __('languages.last_active')]);
            }

            if (Language::query()
                ->where('locale', $data['locale'])
                ->when($id, fn ($q) => $q->whereKeyNot($id))
                ->exists()) {
                throw ValidationException::withMessages([
                    'locale' => __('languages.duplicate'),
                ]);
            }

            $before = $record->exists ? $record->toArray() : [];

            Hooks::action('jobic.settings.languages.saving', $context);

            try {
                $result = $id ? LanguageService::update($id, $data) : LanguageService::store($data);
                $record = $result->data->resource;
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages([
                    'locale' => __('languages.duplicate'),
                ]);
            }

            app(RecordHistory::class)->record('languages', $record->id, $context['user_id'], $context['operation'], $before, $record->toArray());

            $version = RecordVersion::token($record->fresh(), 'languages');

            return $record;
        });

        if ($record instanceof Response) {
            return $record;
        }

        Hooks::notify('jobic.settings.languages.saved', array_replace($context, ['id' => $record->id]));

        return Response::make(true, __('languages.saved'), [
            'version'  => $version,
            'id'       => $record->id,
            'edit_url' => route('admin.settings.languages.edit', $record->id),
            'save_url' => route('admin.settings.languages.update', $record->id),
        ], $id ? 200 : 201);
    }

    /**
     * Apply an all-or-nothing bulk mutation to explicitly selected records.
     *
     * @param array<string, mixed> $input
     *
     * @throws Throwable
     */
    public function bulk(array $input, int $userId): Response
    {
        $data = dto($input, BulkRequest::class);

        $contexts = DB::transaction(function () use ($data, $userId) {
            $all = Language::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $records = $all->whereIn('id', $data['ids']);

            if ($data['operation'] !== 'activate' && $all->where('status', true)
                    ->whereNotIn('id', $data['ids'])
                    ->isEmpty()) {
                throw ValidationException::withMessages(['ids' => __('languages.last_active')]);
            }

            if ($records->count() !== count($data['ids'])) {
                throw ValidationException::withMessages(['ids' => __('languages.missing')]);
            }

            $contexts = [];
            foreach ($records as $record) {
                $context = [
                    'id'        => $record->id,
                    'user_id'   => $userId,
                    'operation' => $data['operation'],
                ];

                Hooks::action('jobic.settings.languages.' . ($data['operation'] === 'delete' ? 'deleting' : 'saving'), $context);

                $before = $record->toArray();
                if ($data['operation'] === 'delete') {
                    LanguageService::delete($record->id);
                } else {
                    LanguageService::update($record->id, [
                        'status' => $data['operation'] === 'activate',
                    ]);
                }

                $after = $data['operation'] === 'delete' ? [] : $record->fresh()
                    ->toArray();

                app(RecordHistory::class)->record('languages', $record->id, $userId, $data['operation'], $before, $after);
                $contexts[] = $context;
            }

            return $contexts;
        });

        foreach ($contexts as $context) {
            Hooks::notify('jobic.settings.languages.' . ($data['operation'] === 'delete' ? 'deleted' : 'saved'), $context);
        }

        return Response::make(true, __('languages.saved'));
    }

    /**
     * Validate and return the history of an existing language.
     *
     * @param array<string, mixed> $input
     *
     * @throws Throwable
     */
    public function history(int $id, array $input): Response
    {
        $data = dto($input, HistoryRequest::class);
        $this->record($id);

        return app(RecordHistory::class)->page('languages', $id, (int) ($data['page'] ?? 1));
    }
}
