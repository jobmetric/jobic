import $ from '../../../admin/jquery';
import { bb } from 'billboard.js';
import 'billboard.js/dist/billboard.css';
import { initializeDashboard } from '../dashboard-runtime';
import { buildBillboardChartConfigs } from '../dashboard/billboard-chart-configs';
import { readChartTheme } from '../dashboard/chart-theme';

const initializeBillboardCharts = ($page) => {
    const instances = new Map();
    let timers = [];
    let resizeTimer;
    let disposed = false;

    const destroyAll = () => {
        timers.forEach(window.clearTimeout);
        timers = [];
        instances.forEach((chart) => chart.destroy());
        instances.clear();
    };

    const renderAll = () => {
        destroyAll();
        const configs = buildBillboardChartConfigs(readChartTheme());

        $page.find('[data-billboard-chart]').each((_, element) => {
            const slug = element.dataset.billboardChart;
            const card = element.closest('[data-chart-card]');
            const definition = configs[slug];
            card.classList.remove('is-rendered', 'is-error');
            element.replaceChildren();

            try {
                const chart = bb.generate({ ...definition.options, bindto: element, transition: { duration: 350 } });
                instances.set(slug, chart);
                if (definition.onReady) timers.push(...definition.onReady(chart));
                window.requestAnimationFrame(() => {
                    if (!disposed) card.classList.add('is-rendered');
                });
            } catch (error) {
                card.classList.add('is-error');
                console.error(`[Vuero charts] Billboard chart "${slug}" failed`, error);
            }
        });
    };

    renderAll();

    $(window).on('resize.chartsBillboard', () => {
        clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => instances.forEach((chart) => chart.resize()), 120);
    });

    $(window).one('pagehide.chartsBillboard', () => {
        disposed = true;
        clearTimeout(resizeTimer);
        $(window).off('resize.chartsBillboard');
        destroyAll();
    });
};

initializeDashboard('charts-billboardjs', initializeBillboardCharts);
