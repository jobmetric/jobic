<?php

namespace Tests\Feature;

use App\Models\User;
use App\Facades\Hooks;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JobMetric\Media\Models\Media;
use JobMetric\Media\Models\MediaRelation;
use Tests\TestCase;

class MediaManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_copy_move_conflicts_archive_and_delete(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::fake('media_public');
        $folder = $this->postJson(route('admin.media-manager.folders.store'), ['name' => 'تصاویر محصولات', 'parent_id' => 0])->assertCreated()->json('data.id');
        $upload = $this->postJson(route('admin.media-manager.upload'), ['file' => UploadedFile::fake()->image('تصویر اصلی.png'), 'parent_id' => $folder])->assertCreated();
        $id = $upload->json('data.id');
        $upload->assertJsonPath('data.type', 'file');
        $this->get(route('admin.media-manager.content', $id))->assertOk();
        $target = $this->postJson(route('admin.media-manager.folders.store'), ['name' => 'Archive'])->assertCreated()->json('data.id');
        $copy = $this->postJson(route('admin.media-manager.paste'), ['item_ids' => [$id], 'destination_id' => $target, 'mode' => 'copy'])->assertOk()->json('data.processed.0');
        $this->assertNotEquals($id, $copy);
        $this->get(route('admin.media-manager.content', $copy))->assertOk();
        $this->postJson(route('admin.media-manager.paste'), ['item_ids' => [$id], 'destination_id' => $target, 'mode' => 'copy'])->assertConflict()->assertJsonPath('code', 'name_conflict');
        $this->postJson(route('admin.media-manager.paste'), ['item_ids' => [$id], 'destination_id' => $target, 'mode' => 'copy', 'resolution' => 'keep_both'])->assertOk();
        $this->patchJson(route('admin.media-manager.rename', $id), ['name' => 'تصویر ویرایش‌شده.png'])->assertOk();
        $this->postJson(route('admin.media-manager.paste'), ['item_ids' => [$folder], 'destination_id' => $folder, 'mode' => 'move'])->assertUnprocessable();
        $this->postJson(route('admin.media-manager.paste'), ['item_ids' => [$id], 'destination_id' => 0, 'mode' => 'move'])->assertOk();
        $this->assertDatabaseMissing(config('media.tables.media_path'), ['media_id' => $id, 'path_id' => $folder]);
        $archive = $this->postJson(route('admin.media-manager.compress'), ['item_ids' => [$target], 'destination_id' => 0, 'name' => 'photos.zip'])->assertOk()->json('data.item.id');
        $this->getJson(route('admin.media-manager.preview', $archive))->assertOk()->assertJsonPath('data.kind', 'archive');
        $this->postJson(route('admin.media-manager.extract', $archive), ['destination' => 'named_folder'])->assertOk()->assertJsonPath('data.extracted_count', 2);
        $this->deleteJson(route('admin.media-manager.delete', $id))->assertOk();
        $this->assertSoftDeleted(config('media.tables.media'), ['id' => $id]);
    }

    public function test_duplicate_upload_and_used_media_are_protected(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::fake('media_public');
        $file = UploadedFile::fake()->image('same.png');
        $id = $this->postJson(route('admin.media-manager.upload'), ['file' => $file])->assertCreated()->json('data.id');
        $this->postJson(route('admin.media-manager.upload'), ['file' => $file])->assertConflict()->assertJsonPath('code', 'duplicate_same_folder');
        MediaRelation::query()->create(['media_id' => $id, 'mediaable_type' => User::class, 'mediaable_id' => auth()->id(), 'collection' => 'avatar']);
        $this->deleteJson(route('admin.media-manager.delete', $id))->assertConflict()->assertJsonPath('code', 'item_in_use');
        $this->assertNotNull(Media::find($id));
        $this->postJson(route('admin.media-manager.folders.store'),['name' => '../outside'])->assertUnprocessable();
    }

    public function test_commands_validate_before_hooks_and_veto_does_not_persist(): void
    {
        $this->actingAs(User::factory()->create());
        $events = [];
        Hooks::addAction('jobic.media.saving', function (array $context) use (&$events): void {
            $events[] = ['saving', $context];
        });
        Hooks::addAction('jobic.media.saved', function (array $context) use (&$events): void {
            $events[] = ['saved', $context];
        });
        $this->postJson(route('admin.media-manager.folders.store'), [])->assertUnprocessable();
        $this->assertSame([], $events);
        $this->postJson(route('admin.media-manager.folders.store'), ['name' => 'Public folder'])->assertCreated();
        $this->assertSame(['saving', 'saved'], array_column($events, 0));
        $this->assertSame(['operation', 'id', 'user_id'], array_keys($events[0][1]));
        Hooks::addAction('jobic.media.saving', function (): void {
            throw ValidationException::withMessages(['name' => 'Vetoed by extension']);
        });
        $this->postJson(route('admin.media-manager.folders.store'), ['name' => 'Blocked folder'])->assertUnprocessable();
        $this->assertDatabaseMissing(config('media.tables.media'), ['name' => 'Blocked folder']);
        $this->assertSame(['saving', 'saved', 'saving'], array_column($events, 0));
    }

    public function test_browse_filters_are_bounded_and_search_scope_is_respected(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::fake('media_public');
        $folder = $this->postJson(route('admin.media-manager.folders.store'), ['name' => 'Folder'])->assertCreated()->json('data.id');
        $this->postJson(route('admin.media-manager.upload'), ['parent_id' => $folder, 'file' => UploadedFile::fake()->image('needle.png')])->assertCreated();
        $this->getJson(route('admin.media-manager.browse', ['search' => 'needle', 'scope' => 'folder']))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(route('admin.media-manager.browse', ['search' => 'needle', 'scope' => 'global', 'group' => 'image']))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(route('admin.media-manager.browse', ['search' => 'needle', 'scope' => 'global', 'group' => 'video']))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson(route('admin.media-manager.browse', ['per_page' => 10000]))->assertUnprocessable();
        $this->getJson(route('admin.media-manager.browse', ['sort' => 'password']))->assertUnprocessable();
    }
}
