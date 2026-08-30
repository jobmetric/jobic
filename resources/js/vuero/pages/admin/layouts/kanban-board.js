import $ from '../../../admin/jquery';
import { initializeProjectsToolbar } from './projects-runtime';

const updateCounts = ($view) => {
    $view.find('[data-kanban-column]').each(function () {
        const state = String($(this).data('kanban-column'));
        const count = $(this).find('[data-kanban-task]:visible').length;
        $view.find(`[data-kanban-count="${state}"]`).text(count);
        $(this).toggleClass('is-empty', count === 0);
        $(this).find('.kanban-empty').prop('hidden', count !== 0);
    });
};

const announce = (message) => {
    const $toast = $('[data-ui-toast]');
    if (!$toast.length) return;
    $toast.find('[data-ui-toast-message]').text(message);
    $toast.prop('hidden', false);
    requestAnimationFrame(() => $toast.addClass('is-visible'));
    window.setTimeout(() => {
        $toast.removeClass('is-visible');
        window.setTimeout(() => $toast.prop('hidden', true), 220);
    }, 2800);
};

$(() => {
    const $view = $('[data-kanban-board]');
    if (!$view.length) return;

    initializeProjectsToolbar($view);

    let dragged = null;

    $view.on('dragstart', '[data-kanban-task][draggable="true"]', function (event) {
        dragged = this;
        event.originalEvent.dataTransfer.effectAllowed = 'move';
        $(this).addClass('is-dragging');
    });

    $view.on('dragend', '[data-kanban-task]', function () {
        $(this).removeClass('is-dragging');
        dragged = null;
        $view.find('.kanban-board').removeClass('is-drag-over');
    });

    $view.on('dragover', '[data-kanban-droppable="true"]', function (event) {
        event.preventDefault();
        $(this).addClass('is-drag-over');
    });

    $view.on('dragleave', '[data-kanban-droppable="true"]', function () {
        $(this).removeClass('is-drag-over');
    });

    $view.on('drop', '[data-kanban-droppable="true"]', function (event) {
        event.preventDefault();
        $(this).removeClass('is-drag-over');
        if (!dragged) return;

        const targetState = String($(this).data('kanban-board'));
        const currentState = String($(dragged).data('task-state'));
        if (targetState === currentState || targetState === 'completed' || currentState === 'new') return;

        $(this).find('.kanban-empty').prop('hidden', true);
        $(dragged).attr('data-task-state', targetState);
        $(this).append(dragged);
        updateCounts($view);
        announce(`Task moved to ${targetState.replace('-', ' ')}.`);
    });

    $view.on('click', '[data-kanban-collapse]', function () {
        const state = String($(this).data('kanban-collapse'));
        $view.find(`[data-kanban-column="${state}"]`).addClass('is-collapsed');
        $view.find(`[data-kanban-column-wrap="${state}"]`).removeClass('is-one-fifth').addClass('is-1 is-mini');
    });

    $view.on('click', '.kanban-settings-trigger', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $button = $(this);
        const $child = $button.siblings('.child-dropdown');
        const isOpen = $button.attr('aria-expanded') === 'true';
        $button.attr('aria-expanded', String(!isOpen));
        $child.prop('hidden', isOpen);
    });

    $view.on('click', '[data-kanban-settings], [data-kanban-setting]', (event) => event.stopPropagation());

    $view.on('change', '[data-kanban-setting]', function () {
        const setting = String($(this).data('kanban-setting'));
        const state = String($(this).closest('[data-kanban-menu]').find('[data-kanban-collapse]').data('kanban-collapse'));
        const enabled = $(this).prop('checked');
        const $column = $view.find(`[data-kanban-column="${state}"]`);

        if (setting === 'lock') {
            $column.toggleClass('is-locked', enabled);
            $column.find('[data-kanban-task]').attr('draggable', enabled ? 'false' : String($column.hasClass('can-drag')));
        }

        announce(`${setting[0].toUpperCase()}${setting.slice(1)} ${enabled ? 'enabled' : 'disabled'}.`);
    });

    $view.on('click', '[data-kanban-rename]', function () {
        const state = String($(this).data('kanban-rename'));
        const $name = $view.find(`[data-kanban-column="${state}"] .column-name`).first();
        $name.attr('contenteditable', 'true').addClass('is-renaming').trigger('focus');
        const selection = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents($name[0]);
        selection.removeAllRanges();
        selection.addRange(range);
    });

    $view.on('keydown', '.column-name[contenteditable="true"]', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            $(this).trigger('blur');
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            $(this).text($(this).data('original-name')).trigger('blur');
        }
    });

    $view.on('focus', '.column-name[contenteditable="true"]', function () {
        $(this).data('original-name', $(this).text().trim());
    });

    $view.on('blur', '.column-name[contenteditable="true"]', function () {
        const $name = $(this);
        const label = $name.text().trim() || String($name.data('original-name'));
        $name.text(label).removeAttr('contenteditable').removeClass('is-renaming');
        const state = String($name.closest('[data-kanban-column]').data('kanban-column'));
        $view.find(`[data-kanban-column-wrap="${state}"] .collapsed-text`).text(label);
        announce('Column renamed.');
    });

    $view.on('click', '[data-kanban-expand]', function () {
        const state = String($(this).data('kanban-expand'));
        $view.find(`[data-kanban-column="${state}"]`).removeClass('is-collapsed');
        $view.find(`[data-kanban-column-wrap="${state}"]`).addClass('is-one-fifth').removeClass('is-1 is-mini');
    });

    $view.on('click', '[data-kanban-approve]', function () {
        const $card = $(this).closest('[data-kanban-task]');
        const $target = $view.find('[data-kanban-board="progress"]');
        const due = String($card.data('task-due'));
        const owner = String($card.find('.task-owner').attr('src'));
        $card.attr('data-task-state', 'progress').attr('draggable', 'true').removeClass('is-new');
        $card.find('.new-avatar, .card-footer').remove();
        $card.find('.card-body').append(`
            <div class="kanban-card-stats">
                <span><svg class="ui-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><use href="#icon-clock"></use></svg>${due}</span>
                <span class="flex-avatar-stack"><span class="flex-avatar is-small"><img src="${owner}" alt=""></span></span>
            </div>
        `);
        $target.find('.kanban-empty').prop('hidden', true);
        $target.append($card);
        updateCounts($view);
        announce('Task approved and moved to In Progress.');
    });

    $view.on('click', '[data-kanban-new]', () => announce('New task dialog opened.'));
    $view.on('click', '.card-footer-item:not([data-kanban-approve])', () => announce('Task details opened.'));

    $view.on('input', '[data-kanban-search]', function () {
        const query = String($(this).val()).trim().toLocaleLowerCase();
        $view.find('[data-kanban-task]').each(function () {
            const matches = !query || String($(this).data('search')).includes(query);
            $(this).prop('hidden', !matches);
        });
        updateCounts($view);
    });

    updateCounts($view);
});
