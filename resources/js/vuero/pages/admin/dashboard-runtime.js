import $ from '../../admin/jquery';
import { createActionController } from './dashboard/actions';
import { initializeCarousels } from './dashboard/carousels';
import { initializeCharts } from './dashboard/charts';
import { initializeInteractions } from './dashboard/interactions';
import { initializeMotion } from './dashboard/motion';

export const initializeDashboard = (slug, initializePage = null) => {
    $(() => {
        const $dashboard = $(`[data-dashboard-page="${slug}"]`);

        if (!$dashboard.length) {
            return;
        }

        const actions = createActionController($dashboard);
        const charts = initializeCharts($dashboard);

        const carousel = initializeCarousels($dashboard);
        initializeMotion($dashboard);
        initializeInteractions($dashboard, slug, actions, charts);
        if (typeof initializePage === 'function') initializePage($dashboard, actions, charts, carousel);
        window.requestAnimationFrame(() => $dashboard.addClass('is-ready'));

        $dashboard.on('click', '[data-dashboard-tabs] button, .pill-tabs button, .match-table nav button', function () {
            $(this).siblings('button').removeClass('is-active');
            $(this).addClass('is-active');
        });

        $dashboard.on('change', '.todo-list input', function () {
            $(this).closest('label').toggleClass('is-complete', this.checked);
        });

        $dashboard.on('submit', 'form', (event) => event.preventDefault());
    });
};
