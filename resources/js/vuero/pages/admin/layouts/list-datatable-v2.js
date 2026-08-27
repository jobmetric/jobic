import { initializeListView } from './list-view-runtime';
import $ from '../../../admin/jquery';

initializeListView('[data-list-datatable-v2]', {
    initialize: ($view, announce) => {
        $view.on('click', '[data-datatable-cta]', () => {
            announce('New Project dialog opened.');
        });

        $view.on('click', '[data-flex-menu-action]', function () {
            const action = String($(this).data('flex-menu-action'));
            const name = String($(this).closest('[data-layout-item]').data('item-name'));
            const labels = {
                view: `Viewing details for ${name}.`,
                edit: `Edit dialog opened for ${name}.`,
                task: `New task dialog opened for ${name}.`,
                remove: `${name} removed from list.`,
            };
            announce(labels[action] ?? 'Action completed.');
        });
    },
});
