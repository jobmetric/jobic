import { initializeListView } from './list-view-runtime';
import $ from '../../../admin/jquery';

const bindMenuActions = ($view, announce, labels) => {
    $view.on('click', '[data-flex-menu-action]', function () {
        const action = String($(this).data('flex-menu-action'));
        const name = String($(this).closest('[data-layout-item]').data('item-name'));
        announce(labels[action]?.replace(':name', name) ?? 'Action completed.');
    });
};

initializeListView('[data-list-datatable-v1]', {
    initialize: ($view, announce) => {
        $view.on('click', '[data-datatable-cta]', () => {
            announce('Add User dialog opened.');
        });

        bindMenuActions($view, announce, {
            view: 'Viewing details for :name.',
            projects: 'Projects opened for :name.',
            schedule: 'Meeting scheduler opened for :name.',
            remove: ':name removed from list.',
        });
    },
});
