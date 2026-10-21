<?php

declare(strict_types=1);

namespace App\Events\EditorDocument;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe a successful EditorDocument outcome using only approved identifiers.
 */
readonly class EditorDocumentStoreEvent implements DomainEvent
{
    /**
     * Capture safe identifiers for committed outcome consumers.
     *
     * @param int $id
     *
     * @return void
     */
    public function __construct(public int $id)
    {
    }

    /**
     * Return the stable key of this business outcome.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'jobic.editor_document.stored';
    }

    /**
     * Describe the translated outcome for registry discovery.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'events.editor_document_stored.group', 'events.editor_document_stored.title', 'events.editor_document_stored.description', 'fas fa-bolt', [
            'jobic',
            'automation',
            'committed',
        ]);
    }
}
