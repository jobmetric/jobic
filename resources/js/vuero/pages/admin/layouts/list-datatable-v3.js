import { initializeListView } from './list-view-runtime';
import $ from '../../../admin/jquery';

initializeListView('[data-list-datatable-v3]', {
    initialize: ($view, announce) => {
        $view.on('click', '[data-datatable-cta]', () => {
            announce('New Offer dialog opened.');
        });

        $view.on('click', '[data-datatable-apply]', function () {
            const name = String($(this).data('item-name'));
            announce(`Application submitted for ${name}.`);
        });
    },
});
