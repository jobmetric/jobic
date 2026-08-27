import { initializeListView } from './list-view-runtime';

initializeListView('[data-list-view-v2]', {
    initialize: ($view, announce) => {
        $view.on('click', '[data-property-action]', function () {
            const name = String($(this).data('item-name'));
            const action = String($(this).data('property-action'));
            announce(action === 'book' ? `Booking started for ${name}.` : `Property details opened for ${name}.`);
        });
    },
});
