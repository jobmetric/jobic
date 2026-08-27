import { initializeListView } from './list-view-runtime';
import $ from '../../../admin/jquery';

const savedJobs = new Set();

const renderSavedJobs = ($view) => {
    const $savedList = $view.find('[data-saved-items]').empty();
    savedJobs.forEach((id) => {
        const $source = $view.find(`[data-layout-panel="all"] [data-job-id="${id}"]`).first();
        if (!$source.length) return;
        const $clone = $source.clone(false);
        $clone.find('[data-job-action="save"]').addClass('is-saved').attr('aria-pressed', 'true').attr('aria-label', 'Remove saved job');
        $savedList.append($clone);
    });
    $view.find('[data-layout-placeholder="saved"]').prop('hidden', savedJobs.size !== 0);
};

initializeListView('[data-list-view-v3]', {
    onTabChange: (tab, $view) => {
        if (tab === 'saved') renderSavedJobs($view);
    },
    initialize: ($view, announce, filterItems) => {
        $view.on('click', '[data-job-action="apply"]', function () {
            announce(`Application started for ${String($(this).data('item-name'))}.`);
        });
        $view.on('click', '[data-job-action="save"]', function () {
            const $card = $(this).closest('[data-job-id]');
            const id = String($card.data('job-id'));
            const name = String($(this).data('item-name'));
            if (savedJobs.has(id)) savedJobs.delete(id); else savedJobs.add(id);
            const isSaved = savedJobs.has(id);
            $view.find(`[data-job-id="${id}"] [data-job-action="save"]`).toggleClass('is-saved', isSaved).attr('aria-pressed', String(isSaved));
            renderSavedJobs($view);
            filterItems();
            announce(isSaved ? `${name} saved.` : `${name} removed from saved jobs.`);
        });
    },
});
