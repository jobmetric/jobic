<script setup>
import {computed, nextTick, onBeforeUnmount, onMounted, ref} from 'vue';
import {pageData, loadingButton} from '../common/page';
import {sendSettings} from '../admin/settings';
import {announce} from '../common/toast';
import {enhanceSelects} from '../common/selects';
import {iconMarkup} from '../common/icons';
import WorkflowPageHeader from '../workflow/components/WorkflowPageHeader.vue';
import WorkflowSettingsDialog from '../workflow/components/WorkflowSettingsDialog.vue';
import WorkflowToast from '../workflow/components/WorkflowToast.vue';
import '../../css/kanban.css';

const config = pageData().kanban;
const copy = config.copy;
const columns = ref([]);
const definition = ref(null);
const initialLoading = ref(true);
const boardError = ref('');
const busy = ref(false);
const pageSize = ref(config.pageSize);
const selectedSize = ref(config.pageSize);
const settingsError = ref('');
const settingsDialog = ref(null);
const detailDialog = ref(null);
const scheduleDialog = ref(null);
const detail = ref(null);
const detailError = ref('');
const editorFrame = ref(null);
const editorLocale = ref(config.locales[0]?.code);
const frameLoading = ref(false);
const drag = ref(null);
const dropAt = ref(null);
const pendingDrop = ref(null);
const dueLocal = ref('');
const dueError = ref('');
const menu = ref(null);
let lastFocus = null;
let lastCardId = null;
let readGeneration = 0;
let detailGeneration = 0;
let dragGeneration = 0;
let previewPromise = null;
const columnReads = new WeakMap();
let dropping = false;
const liveMessage = ref('');
const destinations = computed(() => drag.value?.destinations ?? []);
const editorUrl = computed(() => {
    if (!detail.value?.edit_url) return null;
    const url = new URL(detail.value.edit_url, location.origin);
    if (editorLocale.value) url.searchParams.set('locale', editorLocale.value);
    return url.href;
});

async function get(url) {
    const response = await fetch(url, {headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'});
    const result = await response.json();
    if (!response.ok || !result.ok) throw Object.assign(new Error(result.message || copy.failed), {status: response.status});
    return result.data;
}
async function reload() {
    const generation = ++readGeneration;
    initialLoading.value = true; boardError.value = '';
    try {
        const data = await get(config.dataUrl);
        if (generation !== readGeneration) return;
        definition.value = data.definition_id;
        columns.value = data.columns.map(column => ({...column, loading: false, error: ''}));
    } catch (error) { if (generation === readGeneration) boardError.value = error.message; }
    finally { if (generation === readGeneration) initialLoading.value = false; }
}
function more(column) {
    if (columnReads.has(column)) return columnReads.get(column);
    if (!column.cursor || busy.value) return Promise.resolve(false);
    const pending = loadMore(column);
    columnReads.set(column, pending);
    pending.finally(() => columnReads.delete(column));
    return pending;
}
async function loadMore(column) {
    const generation = readGeneration;
    column.loading = true; column.error = '';
    try {
        const url = new URL(config.dataUrl, location.origin);
        url.searchParams.set('state', column.status); url.searchParams.set('cursor', column.cursor);
        url.searchParams.set('definition_id', definition.value);
        const data = await get(url);
        if (generation !== readGeneration) return false;
        const incoming = data.columns[0];
        const seen = new Set(column.items.map(item => item.id));
        column.items.push(...incoming.items.filter(item => !seen.has(item.id)));
        column.cursor = incoming.cursor; column.count = incoming.count; column.version = incoming.version;
        return true;
    } catch (error) {
        column.error = error.message;
        if (error.status === 409) { announce(copy.stale, true); await reload(); }
        return false;
    } finally { column.loading = false; }
}
function scrolled(event, column) {
    const element = event.currentTarget;
    if (element.scrollHeight - element.scrollTop - element.clientHeight < 80) more(column);
}
function wheel(event, column) {
    const element = event.currentTarget;
    if (event.deltaY > 0 && element.scrollHeight - element.scrollTop - element.clientHeight < 80) more(column);
}
async function settingsSave(event) {
    if (busy.value) return;
    const restore = loadingButton(event.submitter);
    busy.value = true; settingsError.value = '';
    try {
        const data = new FormData(event.target); data.set('operation', 'settings');
        const result = await sendSettings(config.commandUrl, data);
        pageSize.value = result.data.page_size; selectedSize.value = result.data.page_size;
        settingsDialog.value.close(); announce(result.message); await reload();
    } catch (error) { settingsError.value = error.errors?.page_size?.[0] || error.message; await nextTick(); event.target.querySelector('select')?.focus(); }
    finally { busy.value = false; restore(); }
}
async function openCard(card, event) {
    if (busy.value || drag.value) return;
    lastFocus = event?.currentTarget ?? document.activeElement;
    lastCardId = card.id;
    const generation = ++detailGeneration;
    detail.value = null; detailError.value = ''; frameLoading.value = false;
    detailDialog.value.showModal();
    try {
        const value = await get(config.detailUrl.replace('__ID__', card.id));
        if (generation !== detailGeneration) return;
        detail.value = value; editorLocale.value = value.locale ?? document.documentElement.lang;
        frameLoading.value = Boolean(value.edit_url);
        await nextTick(); enhanceSelects(detailDialog.value);
    } catch (error) { if (generation === detailGeneration) detailError.value = error.message; }
}
function restoreFocus() {
    const replacement = lastCardId ? document.querySelector(`[data-kanban-card="${lastCardId}"] .kb-card-title`) : null;
    (lastFocus?.isConnected ? lastFocus : replacement ?? document.querySelector('.rf-header__back'))?.focus();
}
async function canLeaveEditor() {
    try {
        const editor = editorFrame.value?.contentWindow?.kanbanEditor;
        return editor ? await editor.canClose() : !frameLoading.value;
    } catch { return true; }
}
async function closeDetails() {
    if (busy.value || !(await canLeaveEditor())) return;
    ++detailGeneration; detailDialog.value.close(); detail.value = null;
    await nextTick(); restoreFocus();
}
async function localeChange(event) {
    const selected = event.target.value;
    if (!(await canLeaveEditor())) { event.target.value = editorLocale.value; return; }
    editorLocale.value = selected;
    if (detail.value?.edit_url) frameLoading.value = true;
    else if (detail.value) {
        const url = new URL(config.detailUrl.replace('__ID__', detail.value.card.id), location.origin); url.searchParams.set('locale', selected);
        try { detail.value = await get(url); } catch(error) { announce(error.message, true); }
    }
}
async function changed() {
    if (!detailDialog.value.open) return;
    detailDialog.value.close(); detail.value = null; ++detailGeneration;
    await reload(); restoreFocus();
}
async function saved() {
    const id = detail.value?.card.id;
    const generation = detailGeneration;
    await reload();
    if (!id || !detailDialog.value.open || generation !== detailGeneration) return;
    try {
        const url = new URL(config.detailUrl.replace('__ID__', id), location.origin);
        if (editorLocale.value) url.searchParams.set('locale', editorLocale.value);
        const value = await get(url);
        if (generation === detailGeneration && detailDialog.value.open) detail.value = value;
    } catch (error) { if (generation === detailGeneration) detailError.value = error.message; }
}
async function detailMove(target) {
    if (!detail.value || !(await canLeaveEditor())) return;
    const card = {...detail.value.card};
    detailDialog.value.close(); detail.value=null; ++detailGeneration;
    await chooseMove(card, columns.value.find(c=>c.status===target.state), target);
}
function eligible(column) {
    if (!drag.value) return null;
    if (column.status === drag.value.card.state) return {status: 'ready'};
    return destinations.value.find(target => target.state === column.status) ?? null;
}
async function startDrag(card, event) {
    if (!config.editable || busy.value) { event?.preventDefault(); return; }
    menu.value = null;
    const generation = ++dragGeneration;
    drag.value = {card, destinations: [], loading: true};
    if (event?.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', String(card.id));
    }
    previewPromise = get(config.detailUrl.replace('__ID__', card.id)).then(data => {
        if (generation !== dragGeneration || drag.value?.card.id !== card.id) return;
        drag.value = {...drag.value, card: data.card, destinations: data.destinations, loading: false, definition_id: data.definition_id};
        liveMessage.value = copy.choose_destination;
    }).catch(error => { announce(error.message, true); clearDrag(); });
    await previewPromise;
}
function clearDrag() { drag.value = null; dropAt.value = null; ++dragGeneration; }
function over(event, column, card = null) {
    if (!drag.value || busy.value) return;
    const target = eligible(column);
    if (!drag.value.loading && (!target || target.status === 'blocked')) { if (event.dataTransfer) event.dataTransfer.dropEffect = 'none'; return; }
    event.preventDefault();
    let before = card?.id ?? null;
    if (card) {
        const rect = event.currentTarget.getBoundingClientRect();
        if (event.clientY > rect.top + rect.height / 2) {
            const index = column.items.findIndex(row => row.id === card.id);
            before = column.items[index + 1]?.id ?? null;
        }
    }
    dropAt.value = {state: column.status, before};
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
}
async function dropped(event, column) {
    event.preventDefault(); event.stopPropagation();
    if (!drag.value || busy.value || dropping) return;
    dropping = true;
    const generation = dragGeneration;
    try {
        await previewPromise;
        if (generation !== dragGeneration || !drag.value || drag.value.loading) return;
        const active = drag.value;
        const destination = eligible(column);
        if (!destination || destination.status === 'blocked') { clearDrag(); return; }
        let before = dropAt.value?.state === column.status ? dropAt.value.before : null;
        if (before == null && column.cursor) {
            const length = column.items.length;
            const loaded = await more(column);
            if (!loaded || generation !== dragGeneration) { clearDrag(); return; }
            before = column.items[length]?.id ?? null;
        }
        if (before === active.card.id && active.card.state === column.status) { clearDrag(); return; }
        const operation = active.card.state === column.status ? 'reorder' : 'move';
        const payload = {operation, post_id: active.card.id, definition_id: active.definition_id ?? definition.value,
            version: active.card.version, revision_id: active.card.revision_id, state: column.status,
            column_version: column.version, source_column_version: columns.value.find(c => c.status === active.card.state)?.version ?? 0,
            ...(operation === 'move' ? {transition: destination.transition} : {}), ...(before && before !== active.card.id ? {before_id: before} : {})};
        clearDrag();
        if (destination.requires_due && operation === 'move') {
            pendingDrop.value = payload; dueLocal.value = ''; dueError.value = '';
            scheduleDialog.value.showModal(); return;
        }
        await execute(payload);
    } finally { dropping = false; }
}
async function execute(payload, submitter = null) {
    if (busy.value) return;
    busy.value = true;
    const restore = submitter ? loadingButton(submitter) : () => {};
    try {
        const data = new FormData();
        Object.entries(payload).forEach(([key, value]) => { if (value != null) data.set(key, value); });
        const result = await sendSettings(config.commandUrl, data);
        announce(result.message); liveMessage.value = result.message;
        if (scheduleDialog.value.open) scheduleDialog.value.close();
        pendingDrop.value = null; await reload();
    } catch (error) {
        dueError.value = error.errors?.due_local?.[0] || error.errors?.due_at?.[0] || error.message;
        announce(dueError.value, true);
        if ([403, 409, 423].includes(error.status)) await reload();
    } finally { busy.value = false; restore(); }
}
async function scheduleSave(event) {
    if (!dueLocal.value.trim()) { dueError.value = copy.due_error; return; }
    await execute({...pendingDrop.value, due_local: dueLocal.value}, event.submitter);
}
async function chooseMove(card, column, target = null, before = null) {
    await startDrag(card);
    if (!drag.value) return;
    dropAt.value = {state: column.status, before};
    // A named transition also permits a self-transition from the accessible move menu.
    if (target && target.state === card.state) {
        const payload = {operation: 'move', post_id: card.id, definition_id: definition.value, version: drag.value.card.version,
            revision_id: drag.value.card.revision_id, state: target.state, transition: target.transition,
            column_version: column.version, source_column_version: column.version};
        clearDrag();
        if (target.requires_due) { pendingDrop.value=payload; dueLocal.value=''; dueError.value=''; scheduleDialog.value.showModal(); return; }
        await execute(payload); return;
    }
    await dropped({preventDefault() {}, stopPropagation() {}}, column);
}
async function shiftCard(card, column, direction) {
    let index = column.items.findIndex(item => item.id === card.id);
    if (direction > 0 && index + 2 >= column.items.length && column.cursor) await more(column);
    index = column.items.findIndex(item => item.id === card.id);
    const before = direction < 0 ? column.items[index - 1]?.id : column.items[index + 2]?.id;
    await chooseMove(card, column, null, before); menu.value = null;
}
async function openMove(card, event) {
    lastFocus = event.currentTarget;
    await startDrag(card);
    if (drag.value) menu.value = card.id;
}
function endDrag() { if (!dropping && !menu.value && !pendingDrop.value && !busy.value) clearDrag(); }
function keydown(event) {
    if (event.key === 'Escape' && drag.value) { clearDrag(); menu.value = null; }
}
onMounted(() => { reload(); window.addEventListener('jobic:kanban:changed', changed); window.addEventListener('jobic:kanban:saved', saved); window.addEventListener('keydown', keydown); });
onBeforeUnmount(() => { readGeneration++; detailGeneration++; window.removeEventListener('jobic:kanban:changed', changed); window.removeEventListener('jobic:kanban:saved', saved); window.removeEventListener('keydown', keydown); });
</script>

<template>
    <main class="kb-page rf-page" :aria-busy="busy" data-kanban-board>
        <WorkflowPageHeader :title="`${copy.title} · ${config.typeLabel}`" :back-url="config.backUrl" :back-label="copy.back"
            :settings-label="copy.settings" :busy="busy" @settings="settingsDialog.showModal()" />
        <p class="kb-screen-reader" aria-live="polite">{{ liveMessage }}</p>
        <div v-if="boardError" class="kb-empty" role="alert">{{ boardError }}<button type="button" @click="reload">{{ copy.retry }}</button></div>
        <div v-else-if="initialLoading" class="kb-loading" role="status">{{ copy.loading }}</div>
        <div v-else-if="!definition" class="kb-empty">{{ copy.no_workflow }}</div>
        <div v-else class="kb-columns" :class="{'is-moving': drag}" @dragend="endDrag">
            <section v-for="column in columns" :key="column.status" class="kb-column" :data-kanban-column="column.status"
                :class="{'is-eligible': eligible(column)?.status === 'ready', 'is-unknown': eligible(column)?.status === 'unknown', 'is-needs-input': eligible(column)?.requires_due && eligible(column)?.status === 'ready', 'is-blocked': drag && (!eligible(column) || eligible(column)?.status === 'blocked'), 'is-over': dropAt?.state === column.status}"
                @dragover="over($event, column)" @drop="dropped($event, column)">
                <header class="kb-column-header"><span class="kb-state-dot"/><h2>{{ column.name }}</h2><span v-if="!column.restricted" class="kb-count">{{ column.count }}</span><span v-else aria-hidden="true">◈</span></header>
                <div class="kb-column-body" tabindex="0" :aria-label="column.name" @scroll.passive="scrolled($event, column)" @wheel.passive="wheel($event, column)">
                    <p v-if="column.restricted" class="kb-restricted">{{ copy.restricted }}</p>
                    <p v-else-if="!column.items.length" class="kb-column-empty">{{ copy.empty }}</p>
                    <template v-for="(card, index) in column.items" :key="card.id">
                        <div v-if="dropAt?.state === column.status && dropAt.before === card.id" class="kb-placeholder"/>
                        <article class="kb-card" :data-kanban-card="card.id" :draggable="config.editable && !busy"
                            :class="{'is-dragging': drag?.card.id === card.id}" @dragstart="startDrag(card, $event)" @dragover.stop="over($event, column, card)">
                            <div class="kb-card-top"><span class="kb-card-kind"><span v-html="iconMarkup('file-text',17)"/>{{ config.typeLabel }}</span><span class="kb-card-id">#{{ card.id }}</span><span v-if="config.editable" class="kb-card-grip" :title="copy.drag_hint" v-html="iconMarkup('grip-vertical',18)"/></div>
                            <button type="button" class="kb-card-title" @click="openCard(card, $event)">{{ card.title }}</button>
                            <div class="kb-card-meta"><span class="kb-author-mark" aria-hidden="true">{{ Array.from(card.author || '').slice(0,1).join('') }}</span><span class="kb-card-author" :title="card.author">{{ card.author }}</span></div>
                            <footer><time><span v-html="iconMarkup('clock',14)"/>{{ card.updated_at }}</time><button v-if="config.editable" type="button" class="kb-card-move" :aria-label="copy.move" :title="copy.move_hint" :disabled="busy" @click="openMove(card, $event)"><span v-html="iconMarkup('arrows-left-right',18)"/><span>{{ copy.move }}</span></button></footer>
                            <div v-if="menu === card.id" class="kb-move-menu">
                                <p>{{ copy.choose_destination }}</p>
                                <button v-if="index > 0" type="button" @click="shiftCard(card, column, -1)">↑ {{ copy.move_up }}</button>
                                <button v-if="index < column.items.length-1" type="button" @click="shiftCard(card, column, 1)">↓ {{ copy.move_down }}</button>
                                <button v-for="target in destinations" :key="target.transition" type="button" :disabled="target.status === 'blocked'" :title="target.checks.map(c => c.message).filter(Boolean).join(' · ')"
                                    @click="chooseMove(card, columns.find(c => c.status === target.state), target); menu=null">{{ target.label }} <span v-if="target.status==='unknown'">⚠</span></button>
                                <button type="button" @click="menu=null; clearDrag()">{{ copy.cancel }}</button>
                            </div>
                        </article>
                    </template>
                    <div v-if="dropAt?.state === column.status && !dropAt.before" class="kb-placeholder"/>
                    <p v-if="drag?.loading" class="kb-hint">{{ copy.preview_loading }}</p>
                    <p v-else-if="eligible(column)?.status === 'unknown'" class="kb-hint is-warning">{{ copy.unknown }}</p>
                    <p v-if="eligible(column)?.requires_due" class="kb-hint">{{ copy.needs_due }}</p>
                    <p v-if="eligible(column)?.status === 'blocked'" class="kb-hint">{{ eligible(column).checks?.map(c => c.message).filter(Boolean).join(' · ') || copy.blocked }}</p>
                    <p v-if="column.error" class="kb-error" role="alert">{{ column.error }}</p>
                    <button v-if="column.cursor" type="button" class="kb-more" :disabled="column.loading || busy" @click="more(column)">{{ column.loading ? copy.loading : (column.error ? copy.retry : copy.more) }}</button>
                </div>
            </section>
        </div>
        <WorkflowSettingsDialog ref="settingsDialog" class="kb-settings kb-board-settings" :title="copy.settings" :intro="copy.settings_intro" :close-label="copy.close" :busy="busy">
            <form id="kanban-settings-form" @submit.prevent="settingsSave">
                <div class="rf-settings-dialog__option kb-page-size-option">
                    <label for="kb-page-size"><strong>{{ copy.page_size }}</strong><small>{{ copy.page_size_hint }}</small></label>
                    <select id="kb-page-size" name="page_size" v-model="selectedSize" :disabled="busy" :aria-invalid="!!settingsError" :aria-describedby="settingsError ? 'kb-settings-error' : undefined"><option v-for="size in [5,10,20,50]" :key="size" :value="size">{{ size }}</option></select>
                </div>
                <p v-if="settingsError" id="kb-settings-error" class="kb-error" role="alert">{{ settingsError }}</p>
                <p class="rf-settings-dialog__note"><span v-html="iconMarkup('info',18)"/>{{ copy.personal_settings_note }}</p>
            </form>
            <template #footer><button type="button" class="rf-btn rf-btn--secondary" :disabled="busy" @click="settingsDialog.close()">{{ copy.cancel }}</button><button type="submit" form="kanban-settings-form" class="rf-btn rf-btn--primary" :disabled="busy">{{ copy.save }}</button></template>
        </WorkflowSettingsDialog>
        <dialog ref="detailDialog" class="kb-dialog kb-details" @cancel.prevent="closeDetails">
            <header><div><span class="kb-eyebrow">{{ copy.details }} <span v-if="detail">#{{ detail.card.id }}</span></span><h2>{{ detail?.card.title }}</h2></div><button type="button" :disabled="busy" :aria-label="copy.close" @click="closeDetails">×</button></header>
            <p v-if="detailError" class="kb-error" role="alert">{{ detailError }}</p>
            <p v-else-if="!detail" class="kb-loading">{{ copy.details_loading }}</p>
            <template v-else>
                <div class="kb-detail-toolbar"><label for="kb-editor-locale">{{ copy.locale }}</label><select id="kb-editor-locale" :value="editorLocale" @change="localeChange"><option v-for="locale in config.locales" :key="locale.code" :value="locale.code">{{ locale.name }}</option></select></div>
                <div v-if="detail.edit_url" class="kb-frame-wrap"><p v-if="frameLoading" class="kb-loading">{{ copy.loading }}</p><iframe ref="editorFrame" :src="editorUrl" :title="copy.edit" @load="frameLoading=false"/></div>
                <div v-else class="kb-readonly"><p>{{ detail.excerpt }}</p><div class="editor-document" v-html="detail.content_html"/><dl><dt>{{ copy.author }}</dt><dd>{{ detail.card.author }}</dd><dt>{{ copy.updated }}</dt><dd>{{ detail.card.updated_at }}</dd></dl><p>{{ copy.read_only }}</p></div>
                <div v-if="config.editable && detail.destinations.length" class="kb-detail-transitions"><button v-for="target in detail.destinations" :key="target.transition" type="button" :disabled="busy || target.status === 'blocked'" :title="target.checks.map(c => c.message).filter(Boolean).join(' · ')" @click="detailMove(target)">{{ target.label }}</button></div>
            </template>
        </dialog>
        <dialog ref="scheduleDialog" class="kb-dialog kb-settings" @cancel="busy && $event.preventDefault()">
            <form @submit.prevent="scheduleSave"><header><h2>{{ copy.needs_due }}</h2><button type="button" :disabled="busy" :aria-label="copy.close" @click="scheduleDialog.close(); pendingDrop=null">×</button></header>
                <label for="kb-due">{{ copy.due_label }}</label><input id="kb-due" v-model="dueLocal" autocomplete="off" placeholder="YYYY/MM/DD HH:mm" dir="ltr" :disabled="busy" :aria-invalid="!!dueError" aria-describedby="kb-due-hint kb-due-error"/>
                <p id="kb-due-hint" class="kb-hint">{{ copy.due_hint }}</p><p v-if="dueError" id="kb-due-error" class="kb-error" role="alert">{{ dueError }}</p><footer><button type="submit" class="kb-primary" :disabled="busy">{{ copy.continue }}</button></footer>
            </form>
        </dialog>
        <WorkflowToast :close-label="copy.close" />
    </main>
</template>

