import { initializeListView } from './list-view-runtime';
import $ from '../../../admin/jquery';

initializeListView('[data-flex-list-v2]', {
    initialize: ($view, announce) => {
        $view.on('click', '[data-flex-menu-action]', function () {
            const action = String($(this).data('flex-menu-action'));
            const name = String($(this).closest('[data-layout-item]').data('item-name'));
            const labels = {
                view: `Project details opened for ${name}.`,
                edit: `Edit form opened for ${name}.`,
                task: `New task added to ${name}.`,
                remove: `${name} removed from list.`,
            };
            announce(labels[action] ?? 'Action completed.');
        });
    },
});
