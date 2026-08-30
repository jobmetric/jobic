import $ from '../../../admin/jquery';
import { initFormMultiselects } from './form-layout-runtime';

const filterMap = {
    'Shipping Soon': 'shipping',
    'In Progress': 'progress',
    Blocked: 'blocked',
    Archived: 'archived',
};

$(() => {
    const $view = $('[data-projects-v3]');
    if (!$view.length) return;

    initFormMultiselects($view);

    const applyFilter = (label) => {
        const key = filterMap[label] ?? 'shipping';
        $view.find('[data-project-status]').each(function () {
            $(this).prop('hidden', String($(this).data('project-status')) !== key);
        });
    };

    $view.on('multiselect:change', '[data-projects-filter]', function (_event, selection) {
        applyFilter(String(selection?.value ?? ''));
    });

    // Vuero initially renders the complete project grid. Filtering only starts
    // after the visitor deliberately chooses an option.
});
