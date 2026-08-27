import { initializeListView } from './list-view-runtime';
import $ from '../../../admin/jquery';

const savedRecipes = new Set();

const renderSavedRecipes = ($view) => {
    const $savedList = $view.find('[data-saved-items]').empty();
    savedRecipes.forEach((id) => {
        const $source = $view.find(`[data-layout-panel="all"] [data-recipe-id="${id}"]`).first();
        if (!$source.length) return;
        const $clone = $source.clone(false);
        $clone.find('[data-recipe-action="save"]').addClass('is-saved').attr('aria-pressed', 'true').attr('aria-label', 'Remove saved recipe');
        $savedList.append($clone);
    });
    $view.find('[data-layout-placeholder="saved"]').prop('hidden', savedRecipes.size !== 0);
};

initializeListView('[data-list-view-v4]', {
    onTabChange: (tab, $view) => {
        if (tab === 'saved') renderSavedRecipes($view);
    },
    initialize: ($view, announce) => {
        $view.on('click', '[data-recipe-action="view"]', function () {
            announce(`Opening recipe: ${String($(this).data('item-name'))}.`);
        });
        $view.on('click', '[data-recipe-action="save"]', function () {
            const $card = $(this).closest('[data-recipe-id]');
            const id = String($card.data('recipe-id'));
            const name = String($(this).data('item-name'));
            if (savedRecipes.has(id)) savedRecipes.delete(id); else savedRecipes.add(id);
            const isSaved = savedRecipes.has(id);
            $view.find(`[data-recipe-id="${id}"] [data-recipe-action="save"]`)
                .toggleClass('is-saved', isSaved)
                .attr('aria-pressed', String(isSaved))
                .attr('aria-label', isSaved ? 'Remove saved recipe' : `Save ${name}`);
            renderSavedRecipes($view);
            announce(isSaved ? `${name} saved.` : `${name} removed from saved recipes.`);
        });
    },
});
