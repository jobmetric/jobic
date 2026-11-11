<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Announce an authorized board reread without broadcasting content, counts or record identifiers.
 */
class KanbanInvalidated implements ShouldBroadcastNow
{
    /**
     * Keep the type private so the default event serializer cannot expose other attributes.
     *
     * @param string $type
     *
     * @return void
     */
    public function __construct(private string $type)
    {
    }
    /**
     * Address only the authenticated channel for this registered content type.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('jobic.kanban.'.$this->type)];
    }
    /**
     * Return the stable browser notification name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'kanban.invalidated';
    }
    /**
     * Omit all business data and credentials from the transport.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [];
    }
}
