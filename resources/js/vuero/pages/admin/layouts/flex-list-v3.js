import { initializeListView } from './list-view-runtime';
import $ from '../../../admin/jquery';

initializeListView('[data-flex-list-v3]', {
    initialize: ($view, announce) => {
        $view.on('click', '[data-flex-purchase]', function () {
            const name = String($(this).data('item-name'));
            const price = String($(this).data('item-price'));
            announce(`Purchase started for ${name} ($${price}).`);
        });
    },
});
