<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\CalendarInput;
use App\Support\LocalizedDate;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Store per-card editorial work while translating its deadline at the model boundary.
 */
final class KanbanCardMeta extends Model
{
    protected $table = 'kanban_card_meta';

    protected $primaryKey = 'post_id';

    public $incrementing = false;

    protected $fillable = ['post_id', 'assignee_id', 'due_local', 'version'];

    /**
     * Convert the stored Gregorian deadline to and from the active language calendar.
     *
     * @return Attribute<string|null, string|null>
     * @throws ValidationException
     */
    protected function dueLocal(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => ($stored = $this->getRawOriginal('due_at')) === null
                ? null
                : LocalizedDate::format(Carbon::parse($stored, 'UTC')->setTimezone(config('app.timezone'))),
            set: fn (?string $value): array => ['due_at' => $value === null || trim($value) === ''
                ? null
                : Carbon::parse(CalendarInput::parse($value))->utc()->format('Y-m-d H:i:s')],
        );
    }
}
