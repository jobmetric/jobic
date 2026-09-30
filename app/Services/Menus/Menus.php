<?php

declare(strict_types=1);

namespace App\Services\Menus;

use App\Facades\Hooks;
use App\Http\Requests\Menus\MenuRequest;
use App\Services\History\RecordHistory;
use App\Support\RecordVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Taxonomy\Facades\Taxonomy as TaxonomyService;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;
use Throwable;

/**
 * Manage menu containers backed by the taxonomy package.
 */
class Menus
{
    /**
     * Return menu containers in their configured display order.
     *
     * @return Collection<int, Taxonomy>
     */
    public function records(): Collection
    {
        return Taxonomy::query()
            ->with('translations')
            ->where('type', 'menu')
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();
    }

    /**
     * Find one menu container.
     *
     *
     * @throws ModelNotFoundException
     */
    public function record(int $id): Taxonomy
    {
        return Taxonomy::query()->with('translations')->where('type', 'menu')->findOrFail($id);
    }

    /**
     * Validate and persist a menu container through the taxonomy service.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     */
    public function saveRecord(array $input, ?int $id, int $userId): Response
    {
        $data = dto($input, MenuRequest::class);
        $locale = app()->getLocale();
        $context = ['id' => $id, 'user_id' => $userId, 'operation' => $id ? 'update' : 'create'];

        $response = DB::transaction(function () use ($data, $id, $locale, $context): Response {
            $record = $id ? $this->record($id) : null;
            if ($record && ! hash_equals(RecordVersion::token($record, 'menus'), (string) ($data['_version'] ?? ''))) {
                return Response::make(false, __('menus.conflict'), null, 409);
            }

            unset($data['_version']);
            $before = $record?->toArray() ?? [];
            $payload = [
                'type' => 'menu',
                'ordering' => $data['ordering'],
                'status' => $data['status'],
                'slug' => Str::slug($data['name']),
                'translation' => [$locale => ['name' => $data['name']]],
            ];

            Hooks::action('jobic.content.menus.saving', $context);
            $result = $id ? TaxonomyService::update($id, $payload) : TaxonomyService::store($payload);
            if (! ($result['ok'] ?? false)) {
                throw ValidationException::withMessages(['name' => $result['errors'] ?? [$result['message'] ?? __('menus.failed')]]);
            }

            $record = $this->record((int) $result['data']->id);
            TaxonomyTypeRegistry::register('menu_'.$record->id)
                ->label($record->getTranslation('name') ?? __('menus.unnamed'))
                ->hierarchical()
                ->changeStatusInList();

            app(RecordHistory::class)->record('menus', $record->id, $context['user_id'], $context['operation'], $before, $record->toArray());

            return Response::make(true, __('menus.saved'), [
                'version' => RecordVersion::token($record, 'menus'),
                'id' => $record->id,
                'edit_url' => route('admin.menus.edit', $record->id),
                'save_url' => route('admin.menus.update', $record->id),
            ], $id ? 200 : 201);
        });

        if ($response->ok) {
            Hooks::notify('jobic.content.menus.saved', array_replace($context, ['id' => $response->data['id']]));
        }

        return $response;
    }

    /**
     * Delete a menu and every taxonomy item owned by it.
     *
     *
     * @throws Throwable
     */
    public function delete(int $id, int $userId): Response
    {
        $record = $this->record($id);
        $context = ['id' => $id, 'user_id' => $userId, 'operation' => 'delete'];

        DB::transaction(function () use ($record, $context): void {
            Hooks::action('jobic.content.menus.deleting', $context);
            Taxonomy::query()->where('type', 'menu_'.$record->id)->get()->each(function (Taxonomy $item): void {
                TaxonomyService::delete($item->id);
            });
            TaxonomyService::delete($record->id);
            app(RecordHistory::class)->record('menus', $record->id, $context['user_id'], 'delete', $record->toArray(), []);
        });

        TaxonomyTypeRegistry::unregister('menu_'.$id);
        Hooks::notify('jobic.content.menus.deleted', $context);

        return Response::make(true, __('menus.deleted'));
    }
}
