<?php

declare(strict_types=1);

namespace App\Events\Taxonomy;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe the synchronous Taxonomy DeletingRecord boundary without exposing persistence inputs.
 */
readonly class TaxonomyDeletingRecordEvent implements DomainEvent
{
    /** @var array<string, int|string|null> */
    public array $context;

    /**
     * Copy only approved scalar scope, excluding models, input arrays and field values.
     *
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function __construct(array $context)
    {
        $safe = [];
        foreach (
            [
                'id',
                'post_id',
                'revision_id',
                'user_id',
                'owner_id',
                'owner_type',
                'type',
                'locale',
                'field',
                'section',
                'operation',
                'application',
                'key',
                'form_name',
            ] as $key) {
            if (array_key_exists($key, $context) && (is_int($context[$key]) || is_string($context[$key]) || $context[$key] === null)) {
                $safe[$key] = $context[$key];
            }
        }
        $this->context = $safe;
    }

    /**
     * Return the stable lifecycle key shared with the compatibility hook.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'jobic.taxonomies.deleting-record';
    }

    /**
     * Expose discovery metadata while excluding this boundary from automation triggers.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'lifecycle.jobic_taxonomies_deleting_record.group', 'lifecycle.jobic_taxonomies_deleting_record.title', 'lifecycle.jobic_taxonomies_deleting_record.description', 'fas fa-shield-alt', [
            'lifecycle',
            'synchronous',
            'phase:before',
            'automation:disabled',
        ]);
    }
}
