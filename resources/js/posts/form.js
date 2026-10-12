import { createPostEditor } from './editor-runtime';
import { bindEditorWorkbench } from './editor-workbench';
import { announce } from '../common/toast';
import { guardUnsaved } from '../common/unsaved';
import { loadingButton } from '../common/page';
import { sendSettings } from '../admin/settings';
import { showDialog } from '../taxonomies/shared';
import { bindMediaManager } from '../taxonomies/media-manager';
import { pageData } from '../common/page';
import { iconMarkup } from '../common/icons';
import { enhanceSelects } from '../common/selects';
import { bindHistory } from '../taxonomies/history';
import $ from '../vuero/admin/jquery';

const editorConfig = pageData().postEditor;
Object.defineProperty(editorConfig, 'messages', {get: () => pageData().postEditor.messages});

const form = document.querySelector('[data-post-form]');
if (form) {
    form.noValidate = true;
    enhanceSelects(form);
    let editLockTimer = null;
    let editLockListenersBound = false;
    let editLockLost = false;
    const startEditLockRenewal = () => {
        const token = form.elements.namedItem('_edit_lock');
        if (!token?.value || editLockTimer || editLockLost) return;
        const renew = async () => {
            if (editLockLost || document.hidden) return;
            try {
                const data = new FormData(); data.set('_edit_lock', token.value);
                await sendSettings(form.dataset.editLockRenewUrl, data);
            } catch (error) {
                if (error.status === 423) {
                    editLockLost = true;
                    window.clearInterval(editLockTimer);
                    editLockTimer = null;
                    announce(pageData().posts.edit_lock_lost, true);
                    form.querySelectorAll('button[type="submit"]').forEach((button) => { button.disabled = true; });
                }
            }
        };
        editLockTimer = window.setInterval(renew, 60_000);
        if (!editLockListenersBound) {
            editLockListenersBound = true;
            document.addEventListener('visibilitychange', () => { if (!document.hidden) renew(); });
            window.addEventListener('pagehide', (event) => {
                if (event.persisted) return;
                window.clearInterval(editLockTimer);
                if (!token.value || !form.dataset.editLockReleaseUrl) return;
                const data = new URLSearchParams();
                data.set('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                data.set('_edit_lock', token.value);
                navigator.sendBeacon(form.dataset.editLockReleaseUrl, data);
            }, {once: true});
        }
    };
    startEditLockRenewal();
    form.querySelectorAll('[data-post-form-tabs]').forEach((tablist) => {
        const tabs = Array.from(tablist.querySelectorAll('[data-post-form-tab]'));
        const activateTab = (tab, focus = false) => {
            const name = tab.dataset.postFormTab;
            tabs.forEach((item) => {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', String(active));
                item.tabIndex = active ? 0 : -1;
                const panel = form.querySelector(`#${CSS.escape(item.getAttribute('aria-controls'))}`);
                if (panel) panel.hidden = !active;
            });
            if (focus) tab.focus();
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activateTab(tab));
            tab.addEventListener('keydown', (event) => {
                const rtl = getComputedStyle(tablist).direction === 'rtl';
                let nextIndex = null;
                if (event.key === 'Home') nextIndex = 0;
                if (event.key === 'End') nextIndex = tabs.length - 1;
                if (event.key === 'ArrowRight') nextIndex = (index + (rtl ? -1 : 1) + tabs.length) % tabs.length;
                if (event.key === 'ArrowLeft') nextIndex = (index + (rtl ? 1 : -1) + tabs.length) % tabs.length;
                if (nextIndex === null) return;
                event.preventDefault();
                activateTab(tabs[nextIndex], true);
            });
        });
    });
    const dateInput = form.querySelector('[data-post-datepicker]');
    if (dateInput && typeof $.fn?.datetimepicker === 'function') {
        const jalaliCalendar = ['jalali', 'persian'].includes(dateInput.dataset.calendar);
        const formatSelectedJalaliDate = function (dateText, timepicker) {
            if (!jalaliCalendar) return;

            const instance = $.datepicker?._getInst(this);
            if (!instance) return;

            const year = Number(instance.selectedYear);
            const month = Number(instance.selectedMonth) + 1;
            const day = Number(instance.selectedDay);
            if (![year, month, day].every(Number.isFinite)) return;

            const timeFromText = String(dateText || '').match(/([0-2]?\d:[0-5]\d)(?:\s|$)/)?.[1];
            const time = timepicker?.formattedTime || timeFromText || '00:00';
            const twoDigits = value => String(value).padStart(2, '0');
            this.value = `${year}/${twoDigits(month)}/${twoDigits(day)} ${time}`;
            instance.currentYear = instance.selectedYear = year;
            instance.currentMonth = instance.selectedMonth = month - 1;
            instance.currentDay = instance.selectedDay = day;
        };
        $(dateInput).datetimepicker({
            dateFormat: jalaliCalendar ? 'yy/mm/dd' : 'yy-mm-dd',
            timeFormat: 'HH:mm',
            separator: ' ',
            showButtonPanel: false,
            changeMonth: true,
            changeYear: true,
            controlType: 'select',
            oneLine: false,
            showTimezone: false,
            showTime: true,
            onSelect: formatSelectedJalaliDate,
        });

        const pickerElement = document.querySelector('#ui-datepicker-div') || $.datepicker?.dpDiv?.[0];
        if (pickerElement) {
            // jQuery UI creates the shared popup before its first open; keep it hidden until focus opens it.
            pickerElement.style.display = 'none';

            const enhanceDateSelects = () => {
                pickerElement.querySelectorAll('.ui-datepicker-month, .ui-datepicker-year, .ui-timepicker-select[data-unit="hour"], .ui-timepicker-select[data-unit="minute"]').forEach(select => {
                    if (select.dataset.cmsSelectEnhanced === 'true') return;
                    const kind = select.classList.contains('ui-datepicker-month') ? 'month'
                        : select.classList.contains('ui-datepicker-year') ? 'year'
                            : select.dataset.unit;
                    const wrapper = document.createElement('div');
                    wrapper.className = `cms-datepicker-select cms-datepicker-select--${kind}`;
                    wrapper._selectElement = select;
                    const trigger = document.createElement('button');
                    trigger.type = 'button';
                    trigger.className = 'cms-datepicker-select__trigger';
                    trigger.setAttribute('aria-haspopup', 'listbox');
                    const panel = document.createElement('div');
                    panel.className = `cms-datepicker-select__options cms-datepicker-select__options--${kind}`;
                    panel.setAttribute('role', 'listbox');
                    wrapper.append(trigger, panel);
                    select.before(wrapper);
                    select.dataset.cmsSelectEnhanced = 'true';

                    const syncOptions = () => {
                        const selected = select.options[select.selectedIndex];
                        trigger.textContent = selected?.textContent || '';
                        trigger.setAttribute('aria-expanded', 'false');
                        panel.replaceChildren();
                        const options = Array.from(select.options);
                        if (kind === 'year') {
                            let start = Math.floor(Math.max(0, select.selectedIndex) / 12) * 12;
                            const header = document.createElement('div');
                            header.className = 'cms-datepicker-select__year-header';
                            const previous = document.createElement('button');
                            previous.type = 'button';
                            previous.className = 'cms-datepicker-select__step';
                            previous.textContent = '‹';
                            previous.setAttribute('aria-label', '۱۲ سال قبل');
                            const range = document.createElement('span');
                            range.className = 'cms-datepicker-select__year-range';
                            const next = document.createElement('button');
                            next.type = 'button';
                            next.className = 'cms-datepicker-select__step';
                            next.textContent = '›';
                            next.setAttribute('aria-label', '۱۲ سال بعد');
                            const yearGrid = document.createElement('div');
                            yearGrid.className = 'cms-datepicker-select__year-grid';
                            const renderYears = () => {
                                const first = options[start]?.textContent || '';
                                const last = options[Math.min(start + 11, options.length - 1)]?.textContent || '';
                                range.textContent = `${first} تا ${last}`;
                                yearGrid.replaceChildren();
                                options.slice(start, start + 12).forEach(option => yearGrid.append(makeOption(option)));
                                previous.disabled = start <= 0;
                                next.disabled = start + 12 >= options.length;
                            };
                            previous.addEventListener('click', () => { start = Math.max(0, start - 12); renderYears(); });
                            next.addEventListener('click', () => { start = Math.min(Math.max(0, options.length - 12), start + 12); renderYears(); });
                            header.append(previous, range, next);
                            panel.append(header, yearGrid);
                            renderYears();
                            return;
                        }
                        panel.style.setProperty('--calendar-option-columns', kind === 'hour' ? '6' : kind === 'minute' ? '6' : '3');
                        options.forEach(option => panel.append(makeOption(option)));

                        function makeOption(option) {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'cms-datepicker-select__option';
                            item.textContent = option.textContent;
                            item.dataset.value = option.value;
                            item.setAttribute('role', 'option');
                            item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                            if (option.selected) item.classList.add('is-selected');
                            item.addEventListener('click', () => {
                                select.value = option.value;
                                if (kind === 'month' || kind === 'year') {
                                    const instance = $.datepicker._getInst(dateInput);
                                    const value = Number(option.value);
                                    if (kind === 'year') {
                                        instance.selectedYear = instance.drawYear = instance.currentYear = value;
                                    } else {
                                        instance.selectedMonth = instance.drawMonth = instance.currentMonth = value;
                                    }
                                    instance.selectedDay = instance.currentDay = Math.min(
                                        instance.selectedDay || instance.currentDay || 1,
                                        $.datepicker._getDaysInMonth(instance.drawYear, instance.drawMonth),
                                    );
                                    $.datepicker._notifyChange(instance);
                                    $.datepicker._updateDatepicker(instance);
                                } else {
                                    $(select).trigger('change');
                                }
                                wrapper.classList.remove('is-open');
                                trigger.setAttribute('aria-expanded', 'false');
                            });
                            return item;
                        }
                    };
                    syncOptions();
                    trigger.addEventListener('click', () => {
                        pickerElement.querySelectorAll('.cms-datepicker-select').forEach(control => {
                            if (!control._selectElement?.isConnected) control.remove();
                        });
                        pickerElement.querySelectorAll('.cms-datepicker-select.is-open').forEach(open => {
                            if (open !== wrapper) {
                                open.classList.remove('is-open');
                                open.querySelector('.cms-datepicker-select__options')?.classList.remove('is-open');
                                open.querySelector('.cms-datepicker-select__trigger')?.setAttribute('aria-expanded', 'false');
                            }
                        });
                        const opening = !wrapper.classList.contains('is-open');
                        wrapper.classList.toggle('is-open', opening);
                        panel.classList.toggle('is-open', opening);
                        trigger.setAttribute('aria-expanded', String(opening));
                        if (opening) {
                            const rect = pickerElement.getBoundingClientRect();
                            const triggerRect = trigger.getBoundingClientRect();
                            const width = Math.min(270, rect.width - 16);
                            panel.style.width = `${width}px`;
                            panel.style.left = `${(rect.width - width) / 2}px`;
                            panel.style.top = `${Math.max(42, triggerRect.bottom - rect.top + 7)}px`;
                        }
                    });
                    select.addEventListener('change', syncOptions);
                });
                pickerElement.querySelectorAll('.cms-datepicker-select').forEach(wrapper => {
                    if (!wrapper._selectElement?.isConnected) wrapper.remove();
                });
            };
            enhanceDateSelects();
            new MutationObserver(enhanceDateSelects).observe(pickerElement, {childList: true, subtree: true});
            document.addEventListener('click', event => {
                if (pickerElement.contains(event.target)) return;
                pickerElement.querySelectorAll('.cms-datepicker-select.is-open').forEach(open => {
                    open.classList.remove('is-open');
                    open.querySelector('.cms-datepicker-select__options')?.classList.remove('is-open');
                    open.querySelector('.cms-datepicker-select__trigger')?.setAttribute('aria-expanded', 'false');
                });
            });
        }

        if (jalaliCalendar) {
            const toPersianDigits = value => value.replace(/[0-9]/g, digit => String.fromCharCode(digit.charCodeAt(0) + 1728));
            // jQuery UI reads the anchor's HTML with parseInt() when a day is
            // clicked. Keep that numeric value separately before localizing it.
            const selectDay = $.datepicker?._selectDay;
            if (selectDay) {
                $.datepicker._selectDay = function (id, month, year, cell) {
                    const anchor = cell?.querySelector('a');
                    const day = Number(anchor?.dataset.calendarDay);
                    if (!Number.isFinite(day)) return selectDay.call(this, id, month, year, cell);

                    const input = $(id)[0];
                    if (this._isDisabledDatepicker(input)) return;
                    const instance = this._getInst(input);
                    if ($(cell).hasClass(this._unselectableClass)) return;

                    instance.selectedDay = instance.currentDay = day;
                    instance.selectedMonth = instance.currentMonth = month;
                    instance.selectedYear = instance.currentYear = year;
                    this._selectDate(id, this._formatDate(instance, day, month, year));
                };
            }
            const localizeCalendarDigits = () => {
                const picker = document.querySelector('#ui-datepicker-div');
                if (!picker) return;

                picker.querySelectorAll('.ui-datepicker-calendar td a, .ui-datepicker-calendar td span, .ui-datepicker-title option, .ui-timepicker-div select option, .cms-datepicker-select__trigger, .cms-datepicker-select__option, .cms-datepicker-select__year-range').forEach(element => {
                    if (element.matches('.ui-datepicker-calendar td a') && !element.dataset.calendarDay) {
                        element.dataset.calendarDay = element.textContent.trim();
                    }
                    const localized = toPersianDigits(element.textContent);
                    if (localized !== element.textContent) element.textContent = localized;
                });

                const timeDisplay = picker.querySelector('.ui_tpicker_time_input');
                if (timeDisplay && timeDisplay.value) timeDisplay.value = toPersianDigits(timeDisplay.value);

                const timeControl = picker.querySelector('.ui-timepicker-div .ui_tpicker_time');
                if (timeControl && !timeControl.querySelector('[data-datepicker-now]')) {
                    const nowButton = document.createElement('button');
                    nowButton.type = 'button';
                    nowButton.className = 'ui-datepicker-now';
                    nowButton.dataset.datepickerNow = '';
                    nowButton.textContent = 'الان';
                    timeControl.append(nowButton);
                }
            };
            const picker = pickerElement;
            if (picker) {
                localizeCalendarDigits();
                new MutationObserver(localizeCalendarDigits).observe(picker, {childList: true, subtree: true, characterData: true});
                picker.addEventListener('change', () => requestAnimationFrame(localizeCalendarDigits));
                picker.addEventListener('click', event => {
                    if (!event.target.closest('[data-datepicker-now]')) return;
                    const instance = $.datepicker?._getInst(dateInput);
                    if (!instance) return;
                    const now = new Date();
                    instance.drawMonth = now.getMonth();
                    instance.drawYear = now.getFullYear();
                    $.datepicker._updateDatepicker(instance);
                });
            }
        }
    }
    bindMediaManager(form);
    form.querySelectorAll('[data-taxonomy-quick-add]').forEach(group => {
        const openButton = group.querySelector('[data-taxonomy-add-open]');
        const dialog = group.querySelector('[data-taxonomy-dialog]');
        const nameInput = group.querySelector('[data-taxonomy-name]');
        const parentInput = group.querySelector('[data-taxonomy-parent]');
        const createButton = group.querySelector('[data-taxonomy-create]');
        const nameError = group.querySelector('[data-taxonomy-name-error]');
        const parentError = group.querySelector('[data-taxonomy-parent-error]');
        if (!openButton || !dialog || !nameInput || !createButton) return;

        openButton.addEventListener('click', () => {
            nameError.textContent = '';
            if (parentError) parentError.textContent = '';
            if (!dialog.open) dialog.showModal();
            requestAnimationFrame(() => nameInput.focus());
        });
        dialog.querySelectorAll('[data-taxonomy-dialog-close]').forEach(button => button.addEventListener('click', () => dialog.close()));

        const create = async () => {
            const name = nameInput.value.trim();
            if (!name || createButton.disabled) {
                nameError.textContent = name ? '' : pageData().posts.taxonomy_name_required;
                nameInput.focus();
                return;
            }
            nameError.textContent = '';
            if (parentError) parentError.textContent = '';
            createButton.disabled = true;
            let errorField = '';
            try {
                const payload = new FormData();
                payload.append('translation[' + group.dataset.locale + '][name]', name);
                payload.append('ordering', '0');
                payload.append('status', '1');
                if (group.dataset.hierarchical === 'true' && parentInput) payload.append('parent_id', parentInput.value);
                const response = await fetch(group.dataset.url, {method: 'POST', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value}, body: payload});
                const result = await response.json();
                if (!response.ok || !result.ok || !result.data?.id) {
                    errorField = Object.keys(result.errors || {})[0] || '';
                    throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || editorConfig.messages.failed);
                }
                const choices = group.querySelector('.post-taxonomy-options');
                const parentId = group.dataset.hierarchical === 'true' ? (parentInput?.value || '') : '';
                const parentChoice = parentId ? choices.querySelector(`[data-taxonomy-id="${CSS.escape(parentId)}"]`) : null;
                const parentDepth = parentChoice ? Number(parentChoice.dataset.depth || 0) : -1;
                const depth = parentChoice ? parentDepth + 1 : 0;
                const choice = document.createElement('label');
                choice.className = 'post-taxonomy-choice';
                choice.dataset.taxonomyChoice = '';
                choice.dataset.taxonomyId = String(result.data.id);
                choice.dataset.parentId = parentId;
                choice.dataset.depth = String(depth);
                const control = document.createElement('input');
                control.type = group.dataset.multiple === 'true' ? 'checkbox' : 'radio';
                control.name = `taxonomies[${group.dataset.collection}][]`;
                control.value = String(result.data.id);
                control.checked = true;
                const label = document.createElement('span');
                label.textContent = `${'— '.repeat(depth)}${name}`;
                choice.append(control, label);

                let before = null;
                if (!parentChoice) {
                    before = choices.querySelector('[data-taxonomy-choice]');
                } else {
                    let next = parentChoice.nextElementSibling;
                    while (next && Number(next.dataset.depth || 0) > parentDepth) next = next.nextElementSibling;
                    before = next;
                }
                choices.insertBefore(choice, before);
                if (parentInput) {
                    const parentOption = document.createElement('option');
                    parentOption.value = String(result.data.id);
                    parentOption.dataset.depth = String(depth);
                    parentOption.textContent = `${'— '.repeat(depth)}${name}`;
                    parentInput.add(parentOption);
                }
                control.dispatchEvent(new Event('change', {bubbles: true}));
                nameInput.value = '';
                if (parentInput) {
                    parentInput.value = '';
                    window.jQuery?.(parentInput).trigger('change');
                }
                dialog.close();
            } catch (failure) {
                const message = failure.message || editorConfig.messages.failed;
                if (parentError && errorField === 'parent_id') parentError.textContent = message;
                else nameError.textContent = message;
            } finally { createButton.disabled = false; }
        };
        createButton.addEventListener('click', create);
        nameInput.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); create(); } });
    });
    const labels = new Proxy({}, {get: (_, key) => pageData().postEditor.formLabels[key]});
    let historyPanel = bindHistory(form, () => pageData().posts);
    const toolbar = form.querySelector('[data-crud-toolbar]');
    toolbar.replaceChildren();
    const cancel = document.createElement('a');
    cancel.href = editorConfig.indexUrl;
    cancel.className = 'vuero-button crud-cancel';
    cancel.dataset.crudAction = 'cancel';
    cancel.textContent = labels.cancel;
    const saveGroup = document.createElement('div');
    saveGroup.className = 'crud-split-save';
    const save = document.createElement('button');
    save.type = 'submit'; save.name = 'intent'; save.value = 'save';
    save.className = 'vuero-button is-primary'; save.textContent = labels.save;
    const options = document.createElement('details');
    options.className = 'crud-dropdown';
    const summary = document.createElement('summary');
    summary.className = 'vuero-button'; summary.setAttribute('aria-label', labels.save_options);
    summary.innerHTML = iconMarkup('chevron-down', 18);
    const menu = document.createElement('div'); menu.className = 'crud-dropdown__menu';
    [['save-close', labels.save_close], ['save-new', labels.save_new]].forEach(([value, text]) => {
        const option = document.createElement('button'); option.type = 'submit'; option.name = 'intent'; option.value = value; option.textContent = text; menu.append(option);
    });
    options.append(summary, menu); saveGroup.append(save, options); toolbar.append(cancel, saveGroup);
    window.addEventListener('cms:presentation-refreshed', () => {
        cancel.textContent = labels.cancel;
        save.textContent = labels.save;
        summary.setAttribute('aria-label', labels.save_options);
        menu.querySelector('[value="save-close"]').textContent = labels.save_close;
        menu.querySelector('[value="save-new"]').textContent = labels.save_new;
    });
    const editors = new Map();
    const pendingEditors = new Map();
    const guard = guardUnsaved(form, () => showDialog({
        title: labels.unsaved_title,
        description: editorConfig.messages.unsaved,
        confirm: labels.leave,
    }));

    const createEditor = async holder => {
        if (!holder) return;
        const locale = holder.dataset.locale;
        if (editors.has(locale) || pendingEditors.has(locale)) return pendingEditors.get(locale);
        let initial;
        const persisted = form.querySelector(`[data-editor-content="${CSS.escape(locale)}"]`)?.value || holder.dataset.content || '';
        try { initial = JSON.parse(persisted); } catch { initial = undefined; }
        const ready = createPostEditor(holder, editorConfig, {
            type: form.dataset.type, locale, data: initial, direction: holder.closest('[data-locale-panel]')?.dir || 'auto',
            onChange: async (api) => {
                const input = form.querySelector(`[data-editor-content="${CSS.escape(locale)}"]`);
                if (!input) return;
                input.value = JSON.stringify(await api.saver.save());
                input.dispatchEvent(new Event('input', {bubbles: true}));
            },
        }).then((editor) => { if (editor) { editors.set(locale, editor); bindEditorWorkbench(holder, editor, editorConfig); } }).catch(() => {
            announce(editorConfig.messages.unavailable, true);
        }).finally(() => pendingEditors.delete(locale));
        pendingEditors.set(locale, ready);
        return ready;
    };
    createEditor(form.querySelector('[data-locale-panel]:not([hidden]) [data-editor]'));

    form.addEventListener('click', async event => {
        const button = event.target.closest('[data-revision-restore]');
        if (!button || !(await showDialog({title: labels.revision_title, description: editorConfig.messages.confirmRevision, confirm: labels.restore_revision}))) return;
        button.disabled = true;
        try {
            const payload = new FormData();
            const editLock = form.elements.namedItem('_edit_lock');
            if (editLock?.value) payload.set('_edit_lock', editLock.value);
            const response = await fetch(button.dataset.url, {method: 'POST', headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':form.querySelector('[name=_token]').value}, body: payload});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || editorConfig.messages.failed);
            announce(result.message);
            window.location.reload();
        } catch (error) {
            announce(error.message || editorConfig.messages.failed, true);
            button.disabled = false;
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.dataset.saving) return;
        const intent = event.submitter?.value || 'save';
        await Promise.all(pendingEditors.values());
        for (const [locale, editor] of editors) {
            await editor.isReady;
            const beforeSave = new CustomEvent('jobic:editor:before-save', {cancelable: true, detail: {locale, data: await editor.save()}});
            if (!window.dispatchEvent(beforeSave)) return;
            form.querySelector(`[data-editor-content="${CSS.escape(locale)}"]`).value = JSON.stringify(beforeSave.detail.data);
        }
        const submitted = guard.snapshot();
        form.querySelectorAll('[data-error-for]').forEach(node => node.textContent = '');
        form.querySelectorAll('[aria-invalid]').forEach(node => { node.removeAttribute('aria-invalid'); node.removeAttribute('aria-describedby'); });
        const restoreButtons = [...form.querySelectorAll('button[type=submit]')].map(loadingButton);
        saveGroup.setAttribute('inert', ''); form.dataset.saving = 'true'; form.setAttribute('aria-busy', 'true');
        try {
            const result = await sendSettings(form.action, new FormData(form));
            announce(result.message);
            window.dispatchEvent(new CustomEvent('jobic:editor:after-save', {detail: {type: form.dataset.type, post: result.data, response: result}}));
            guard.saved(submitted);
            if (result.data?.update_url) {
                if (!form.querySelector('[name=_method]')) form.insertAdjacentHTML('afterbegin', '<input type="hidden" name="_method" value="PUT">');
                let versionInput = form.querySelector('[name=_version]');
                if (!versionInput) {
                    versionInput = document.createElement('input');
                    versionInput.type = 'hidden';
                    versionInput.name = '_version';
                    form.append(versionInput);
                }
                versionInput.value = result.data.version || '';
                let lockInput = form.querySelector('[name=_edit_lock]');
                if (!lockInput && result.data.edit_lock) {
                    lockInput = document.createElement('input'); lockInput.type = 'hidden'; lockInput.name = '_edit_lock'; form.append(lockInput);
                }
                if (lockInput) lockInput.value = result.data.edit_lock || '';
                form.dataset.editLockRenewUrl = result.data.edit_lock_renew_url || form.dataset.editLockRenewUrl;
                form.dataset.editLockReleaseUrl = result.data.edit_lock_release_url || form.dataset.editLockReleaseUrl;
                startEditLockRenewal();
                form.action = `${result.data.update_url}?locale=${encodeURIComponent(editorConfig.activeLocale)}`;
                if (result.data.edit_url) history.replaceState({}, '', `${result.data.edit_url}?locale=${encodeURIComponent(editorConfig.activeLocale)}`);
            }
            const historySection = form.querySelector('[data-record-history]');
            if (result.data?.update_url && historySection?.hidden) {
                historySection.hidden = false;
                historyPanel = bindHistory(form, () => pageData().posts);
            } else if (result.data?.update_url && !historySection) {
                const historyTemplate = form.querySelector('[data-record-history-template]');
                const historyPanelElement = historyTemplate?.content.firstElementChild?.cloneNode(true);
                if (historyPanelElement) {
                    form.after(historyPanelElement);
                    historyPanel = bindHistory(form, () => pageData().posts);
                }
            }
            historyPanel.refresh();
            if (intent === 'save-close') { guard.leave(); location.assign(editorConfig.indexUrl); }
            else if (intent === 'save-new') { guard.leave(); location.assign(editorConfig.createUrl); }
        } catch (error) {
            let displayed = 0;
            Object.entries(error.errors || {}).forEach(([name, messages]) => {
                const fieldName = name.replace(/\.(\d+|[^.]+)/g, '[$1]');
                const field = form.elements.namedItem(name) || form.elements.namedItem(fieldName);
                const errorNode = [...form.querySelectorAll('[data-error-for]')].find(node => node.dataset.errorFor === name || name.startsWith(`${node.dataset.errorFor}.`));
                if (errorNode) { errorNode.textContent = [].concat(messages).join(' '); errorNode.id ||= `post-error-${name.replaceAll('.', '-')}`; displayed++; }
                if (field && errorNode) { field.setAttribute('aria-invalid', 'true'); field.setAttribute('aria-describedby', errorNode.id); }
            });
            form.querySelector('[aria-invalid=true]')?.focus();
            if (error.status !== 422 || displayed === 0) announce(error.message || editorConfig.messages.failed, true);
        } finally {
            restoreButtons.forEach(restore => restore()); saveGroup.removeAttribute('inert'); delete form.dataset.saving; form.removeAttribute('aria-busy');
        }
    });
}
