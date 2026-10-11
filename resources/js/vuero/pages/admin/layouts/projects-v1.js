import $ from '../../../admin/jquery';
import { iconMarkup } from '../../../../common/icons';
import { initializeProjectsToolbar } from './projects-runtime';

const sortKeys = ['username', 'position', 'earnings', 'status', 'contacts'];

const compareRows = (key, direction) => (a, b) => {
    const left = $(a).data(key);
    const right = $(b).data(key);
    const factor = direction === 'asc' ? 1 : -1;

    if (key === 'earnings' || key === 'contacts') {
        return (Number(left) - Number(right)) * factor;
    }

    return String(left).localeCompare(String(right)) * factor;
};

$(() => {
    const $view = $('[data-projects-v1]');
    if (!$view.length) return;

    initializeProjectsToolbar($view);

    let currentSort = { key: null, direction: 'asc' };

    const renderSortIcons = () => {
        $view.find('[data-projects-sort]').each(function () {
            const key = String($(this).data('projects-sort'));
            const icon = currentSort.key === key
                ? (currentSort.direction === 'asc' ? 'sort-up' : 'sort-down')
                : 'sort';
            const label = String($(this).data('sort-label'));
            $(this).html(`${label}${iconMarkup(icon, 14)}`);
        });
    };

    const applySort = (key, direction) => {
        currentSort = { key, direction };
        const $rows = $view.find('[data-projects-row]').get();
        $rows.sort(compareRows(key, direction));
        $view.find('[data-layout-items]').append($rows);
        renderSortIcons();
    };

    $view.find('.flex-table-header span').each(function (index) {
        if (index >= sortKeys.length) return;
        const label = $(this).text();
        const key = sortKeys[index];
        $(this).html(`<button type="button" data-projects-sort="${key}" data-sort-label="${label}">${label}</button>`);
    });

    $view.find('[data-projects-row]').each(function (index) {
        $(this).attr('data-projects-original-index', index);
    });

    $view.on('click', '[data-projects-sort]', function () {
        const key = String($(this).data('projects-sort'));
        if (currentSort.key !== key) {
            applySort(key, 'asc');
            return;
        }

        if (currentSort.direction === 'asc') {
            applySort(key, 'desc');
            return;
        }

        currentSort = { key: null, direction: 'asc' };
        const $rows = $view.find('[data-projects-row]').get();
        $rows.sort((a, b) => Number($(a).data('projects-original-index')) - Number($(b).data('projects-original-index')));
        $view.find('[data-layout-items]').append($rows);
        renderSortIcons();
    });

    renderSortIcons();
});
