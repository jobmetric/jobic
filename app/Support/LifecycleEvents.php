<?php

declare(strict_types=1);

namespace App\Support;

use JobMetric\EventSystem\Contracts\DomainEvent;
use InvalidArgumentException;

/**
 * Resolve explicitly registered business lifecycle boundaries to immutable event classes.
 */
final class LifecycleEvents
{
    /** @var array<string, class-string<DomainEvent>> */
    public const CLASSES = [
        'jobic.posts.kanban.boards.saving' => \App\Events\Kanban\KanbanBoardsSavingEvent::class,
        'jobic.posts.kanban.boards.deleting' => \App\Events\Kanban\KanbanBoardsDeletingEvent::class,
        'jobic.posts.kanban.limits.saving'=>\App\Events\Kanban\KanbanLimitsSavingEvent::class,
        'jobic.posts.kanban.settings.saving' => \App\Events\Kanban\KanbanSettingsSavingEvent::class,
        'jobic.posts.kanban.card_work.saving' => \App\Events\Kanban\KanbanCardWorkSavingEvent::class,
        'jobic.posts.kanban.moving' => \App\Events\Kanban\KanbanMovingEvent::class,
        'jobic.posts.kanban.comment.saving' => \App\Events\Kanban\KanbanCommentSavingEvent::class,
        'jobic.posts.kanban.review.saving' => \App\Events\Kanban\KanbanReviewSavingEvent::class,
        'jobic.posts.kanban.notifications.saving' => \App\Events\Kanban\KanbanNotificationsSavingEvent::class,
        'jobic.posts.kanban.notification.sending' => \App\Events\Kanban\KanbanNotificationSendingEvent::class,
        'jobic.posts.kanban.notification.reading' => \App\Events\Kanban\KanbanNotificationReadingEvent::class,
        'jobic.users.saving' => \App\Events\User\UserSavingEvent::class,
        'jobic.users.deleting' => \App\Events\User\UserDeletingEvent::class,
        'jobic.roles.saving' => \App\Events\Role\RoleSavingEvent::class,
        'jobic.roles.deleting' => \App\Events\Role\RoleDeletingEvent::class,
        'jobic.settings.languages.saving' => \App\Events\Language\LanguageSavingEvent::class,
        'jobic.settings.languages.deleting' => \App\Events\Language\LanguageDeletingEvent::class,
        'jobic.media.saving' => \App\Events\Media\MediaSavingEvent::class,
        'jobic.taxonomies.saving' => \App\Events\Taxonomy\TaxonomySavingEvent::class,
        'jobic.taxonomies.persisted' => \App\Events\Taxonomy\TaxonomyPersistedEvent::class,
        'jobic.taxonomies.deleting' => \App\Events\Taxonomy\TaxonomyDeletingEvent::class,
        'jobic.taxonomies.deleting-record' => \App\Events\Taxonomy\TaxonomyDeletingRecordEvent::class,
        'jobic.posts.saving' => \App\Events\Post\PostSavingEvent::class,
        'jobic.posts.deleting' => \App\Events\Post\PostDeletingEvent::class,
        'jobic.posts.revision.restoring' => \App\Events\PostRevision\PostRevisionRestoringEvent::class,
        'jobic.posts.workflow.saving' => \App\Events\Workflow\WorkflowSavingEvent::class,
        'jobic.editor.document.saving' => \App\Events\EditorDocument\EditorDocumentSavingEvent::class,
        'jobic.settings.general.saving' => \App\Events\General\GeneralSavingEvent::class,
        'jobic.settings.layouts.saving' => \App\Events\Layout\LayoutSavingEvent::class,
        'jobic.settings.seo.saving' => \App\Events\Seo\SeoSavingEvent::class,
        'jobic.settings.appearance.saving' => \App\Events\Appearance\AppearanceSavingEvent::class,
        'jobic.settings.profile.saving' => \App\Events\Profile\ProfileSavingEvent::class,
        'jobic.settings.security.saving' => \App\Events\Security\SecuritySavingEvent::class,
        'jobic.settings.mail.saving' => \App\Events\Mail\MailSavingEvent::class,
        'jobic.settings.cache.saving' => \App\Events\Cache\CacheSavingEvent::class,
        'jobic.settings.dynamic.saving' => \App\Events\DynamicSetting\DynamicSettingSavingEvent::class,
        'jobic.auth.language.saving' => \App\Events\Auth\AuthLanguageSavingEvent::class,
        'jobic.settings.two_factor.saving' => \App\Events\TwoFactor\TwoFactorSavingEvent::class,
    ];

    /**
     * Build a safe lifecycle event from an existing hook context.
     *
     * @param string $key
     * @param array<string, mixed> $context
     *
     * @return DomainEvent
     * @throws InvalidArgumentException
     */
    public static function make(string $key, array $context): DomainEvent
    {
        $class = self::CLASSES[$key] ?? throw new InvalidArgumentException('Unknown lifecycle boundary: '.$key);

        return new $class($context);
    }
}
