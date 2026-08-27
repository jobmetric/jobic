import { initializeListView } from './list-view-runtime';
import $ from '../../../admin/jquery';

initializeListView('[data-flex-list-v1]', {
    initialize: ($view, announce) => {
        $view.on('click', '[data-flex-add-user]', () => {
            announce('Add User dialog opened.');
        });

        $view.on('click', '[data-flex-menu-action]', function () {
            const action = String($(this).data('flex-menu-action'));
            const name = String($(this).closest('[data-layout-item]').data('item-name'));
            const labels = {
                view: `Viewing details for ${name}.`,
                projects: `Projects opened for ${name}.`,
                schedule: `Meeting scheduler opened for ${name}.`,
                remove: `${name} removed from list.`,
            };
            announce(labels[action] ?? 'Action completed.');
        });
    },
});
