<?php

declare(strict_types=1);

namespace App\Kanban\Filters;

use App\Contracts\KanbanFilterDriver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;

/** Filter content by one supported taxonomy collection and its selected terms. */
class TaxonomyFilter implements KanbanFilterDriver
{
    /**
     * Determine whether this driver applies to a registered content type.
     *
     * @param string $type
     *
     * @return bool
     */
    public function supports(string $type): bool
    {
        return PostTypeRegistry::for($type)->getTaxonomyTypes() !== [];
    }

    /**
     * Describe terms grouped by the post type's enabled taxonomy collections.
     *
     * @param string $type
     *
     * @return array<string, mixed>
     */
    public function form(string $type): array
    {
        $collections = PostTypeRegistry::for($type)->getTaxonomyTypes();
        $types = array_column($collections, 'type');
        if ($types === []) {
            return [];
        }
        $groups = [];
        foreach ($collections as $collection=>$definition) {
            $groups[] = ['id' => $collection, 'name' => __(\JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::for($definition['type'])->getLabel())];
        }

        return ['label' => __('kanban.taxonomy'), 'fields' => [
            ['key' => 'collection', 'label' => __('kanban.taxonomy'), 'options' => $groups],
            ['key'=>'ids','label'=>__('kanban.terms'),'multiple'=>true,'dependsOn'=>'collection','optionsBy'=>collect($collections)->map(function ($definition) {
                return Taxonomy::where('type',$definition['type'])->with('translations')->get()->map(fn($term)=>['id'=>$term->id,'name'=>$term->getTranslation('name',app()->getLocale()) ?: '#'.$term->id])->all();
            })->all()]]];
    }

    /**
     * Restrict identifiers to the selected and enabled collection.
     *
     * @param string $type
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(string $type, array $config): array
    {
        $validated = Validator::make($config, ['collection' => ['required', Rule::in(array_keys(PostTypeRegistry::for($type)->getTaxonomyTypes()))],
            'ids' => 'required|array|min:1|max:100', 'ids.*' => ['integer', 'distinct', Rule::exists((new Taxonomy)->getTable(), 'id')->where('type', PostTypeRegistry::for($type)->getTaxonomyTypes()[$config['collection'] ?? '']['type'] ?? '')]])->validate();
        $validated['type'] = PostTypeRegistry::for($type)->getTaxonomyTypes()[$validated['collection']]['type'];

        return $validated;
    }

    /**
     * Match any selected term inside the configured taxonomy collection.
     *
     * @param Builder $query
     * @param array<string, mixed> $config
     *
     * @return void
     */
    public function apply(Builder $query, array $config): void
    {
        $query->whereHas('taxonomies', fn (Builder $terms) => $terms->whereIn((new Taxonomy)->qualifyColumn('id'), $config['ids'])->where((new Taxonomy)->qualifyColumn('type'), $config['type'])->where(config('taxonomy.tables.taxonomy_relation').'.collection', $config['collection']));
    }
}
