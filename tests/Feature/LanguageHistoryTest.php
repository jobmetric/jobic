<?php

namespace Tests\Feature;

use App\Facades\Hooks;
use App\Models\User;
use App\Services\History\RecordHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JobMetric\Language\Models\Language;
use Tests\TestCase;

class LanguageHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_tracks_only_real_changes_with_actor_and_rolls_back_on_veto(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $values = ['name'=>'German','locale'=>'de','direction'=>'ltr','calendar'=>'gregorian','first_day_of_week'=>1,'status'=>true];
        $id = $this->postJson(route('admin.settings.languages.store'), $values + ['password'=>'secret'])->assertCreated()->json('data.id');
        $this->assertDatabaseCount('record_changes', 1);
        $this->putJson(route('admin.settings.languages.update', $id), ['_version' => \App\Support\RecordVersion::token(Language::findOrFail($id), 'languages')] + $values)->assertOk();
        $this->assertDatabaseCount('record_changes', 1);
        $this->putJson(route('admin.settings.languages.update', $id), ['_version' => \App\Support\RecordVersion::token(Language::findOrFail($id), 'languages')] + array_replace($values,['name'=>'Deutsch']))->assertOk();
        $result = $this->getJson(route('admin.settings.languages.history', $id))->assertOk();
        $result->assertJsonPath('data.entries.0.actor', $user->name)->assertJsonPath('data.entries.0.changes.name.before','German')->assertJsonPath('data.entries.0.changes.name.after','Deutsch');
        $this->assertStringNotContainsString('secret', $result->getContent());
        Hooks::addAction('jobic.settings.languages.saving', function () { throw ValidationException::withMessages(['name'=>'veto']); });
        $this->putJson(route('admin.settings.languages.update', $id), ['_version' => \App\Support\RecordVersion::token(Language::findOrFail($id), 'languages')] + $values)->assertUnprocessable();
        $this->assertDatabaseCount('record_changes', 2);
        $this->getJson(route('admin.settings.languages.history', ['language'=>$id,'page'=>0]))->assertUnprocessable();
    }

    public function test_history_is_paginated_and_excludes_secrets_and_guest_access(): void
    {
        $id = Language::first()->id;
        $this->getJson(route('admin.settings.languages.history', $id))->assertUnauthorized();
        $user = User::factory()->create();
        for ($i=0;$i<23;$i++) {
            app(RecordHistory::class)->record('languages', $id, $user->id, 'update', ['name'=>'old'], ['name'=>'new','password'=>'secret','token'=>'secret']);
        }
        $this->actingAs($user)->getJson(route('admin.settings.languages.history', $id))->assertOk()->assertJsonCount(20,'data.entries')->assertJsonPath('data.pages',2);
        $this->assertStringNotContainsString('secret', DB::table('record_changes')->first()->changes);
        $this->getJson(route('admin.settings.languages.history', 999999))->assertNotFound();
    }
    public function test_stale_and_missing_versions_cannot_overwrite_other_editors(): void
    {
        $this->actingAs(User::factory()->create());
        $record = Language::first();
        $values = $record->only(['name','locale','direction','calendar','first_day_of_week','status','flag']);
        $values['_version'] = \App\Support\RecordVersion::token($record, 'languages');
        $url = route('admin.settings.languages.update', $record->id);
        $saved = $this->putJson($url, array_replace($values, ['name'=>'First editor']))->assertOk();
        $this->putJson($url, array_replace($values, ['name'=>'First editor', '_version'=>$saved->json('data.version')]))->assertOk();
        $count = DB::table('record_changes')->count();
        $this->actingAs(User::factory()->create());
        $this->putJson($url, array_replace($values, ['name'=>'Stale editor']))->assertStatus(409);
        unset($values['_version']);
        $this->putJson($url, $values)->assertStatus(409);
        $this->assertDatabaseHas('languages', ['id'=>$record->id,'name'=>'First editor']);
        $this->assertDatabaseCount('record_changes', $count);
    }

}
