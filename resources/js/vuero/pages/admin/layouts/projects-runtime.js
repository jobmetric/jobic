import $ from '../../../admin/jquery';
import { initFormMultiselects } from './form-layout-runtime';

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

export const initializeProjectsToolbar = ($root) => {
    $root.on('click', '[data-projects-new]', () => announce('New project dialog opened.'));
    $root.on('input', '[data-projects-search]', function () {
        const query = String($(this).val()).trim().toLocaleLowerCase();
        $root.find('[data-project-recent], [data-project-card], [data-projects-row]').each(function () {
            const haystack = String($(this).text()).toLocaleLowerCase();
            $(this).closest('.column, .flex-table-item').prop('hidden', query !== '' && !haystack.includes(query));
        });
    });

    $root.on('click', '[data-flex-menu-action]', function () {
        const action = String($(this).data('flex-menu-action'));
        const labels = {
            view: 'Project details opened.',
            edit: 'Project editor opened.',
            archive: 'Project archived.',
            download: 'File download started.',
            'view-task': 'Related task opened.',
            update: 'Upload a new file version.',
            delete: 'File deleted.',
            'edit-role': 'Member role editor opened.',
            message: 'Message composer opened.',
            remove: 'Member removed from the team.',
        };

        announce(labels[action] ?? 'Action completed.');
    });
};

export const initializeProjectTabs = ($root) => {
    const moveNaver = ($active) => {
        const index = $active.index();
        $root.find('.tab-naver').css('transform', `translateX(${index * 100}%)`);
    };

    const activateTab = ($tab, shouldFocus = false) => {
        const tab = String($tab.data('project-tab'));
        $root.find('[data-project-tab]')
            .removeClass('is-active')
            .find('[role="tab"]')
            .attr({ 'aria-selected': 'false', tabindex: '-1' });
        $tab.addClass('is-active');
        const $button = $tab.find('[role="tab"]');
        $button.attr({ 'aria-selected': 'true', tabindex: '0' });
        moveNaver($tab);
        $root.find('[data-project-panel]').prop('hidden', true).removeClass('is-active');
        $root.find(`[data-project-panel="${tab}"]`).prop('hidden', false).addClass('is-active');
        if (shouldFocus) $button.trigger('focus');
    };

    $root.on('click', '[data-project-tab] button', function () {
        activateTab($(this).closest('[data-project-tab]'));
    });

    $root.on('keydown', '[data-project-tab] button', function (event) {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;

        event.preventDefault();
        const $tabs = $root.find('[data-project-tab]');
        const currentIndex = $tabs.index($(this).closest('[data-project-tab]'));
        let nextIndex = currentIndex;

        if (event.key === 'Home') nextIndex = 0;
        if (event.key === 'End') nextIndex = $tabs.length - 1;
        if (event.key === 'ArrowLeft') nextIndex = (currentIndex - 1 + $tabs.length) % $tabs.length;
        if (event.key === 'ArrowRight') nextIndex = (currentIndex + 1) % $tabs.length;

        activateTab($tabs.eq(nextIndex), true);
    });

    moveNaver($root.find('[data-project-tab].is-active').first());
    initFormMultiselects($root);
    $root.on('click', '[data-project-edit]', () => announce('Project editor opened.'));
    $root.on('click', '[data-project-task]', () => announce('Task details opened.'));
    $root.on('click', '[data-flex-menu-action]', function () {
        const action = String($(this).data('flex-menu-action'));
        const labels = {
            download: 'File download started.',
            'view-task': 'Related task opened.',
            update: 'Upload a new file version.',
            delete: 'File deleted.',
            view: 'Member profile opened.',
            'edit-role': 'Member role editor opened.',
            message: 'Message composer opened.',
            remove: 'Member removed from the team.',
        };
        announce(labels[action] ?? 'Action completed.');
    });
};

export const initializeOnboarding = ($root) => {
    $root.on('click', '[data-onboarding-action]', function () {
        announce(`${$(this).text().trim()} selected.`);
    });
};
