import $ from '../../../admin/jquery';
import ApexCharts from 'apexcharts';
import { initializeDashboard } from '../dashboard-runtime';
import { buildApexChartConfigs } from '../dashboard/apex-chart-configs';
import { readChartTheme } from '../dashboard/chart-theme';

const initializeApexCharts = ($page) => {
    const instances = new Map();
    let disposed = false;

    const destroyAll = () => {
        instances.forEach((chart) => chart.destroy());
        instances.clear();
    };

    const renderAll = async () => {
        destroyAll();
        const configs = buildApexChartConfigs(readChartTheme());

        for (const element of $page.find('[data-apex-chart]').get()) {
            const slug = element.dataset.apexChart;
            const card = element.closest('[data-chart-card]');
            card.classList.remove('is-rendered', 'is-error');
            element.replaceChildren();

            try {
                const chart = new ApexCharts(element, configs[slug]);
                instances.set(slug, chart);
                await chart.render();
                if (!disposed) card.classList.add('is-rendered');
            } catch (error) {
                card.classList.add('is-error');
                console.error(`[Vuero charts] Apex chart "${slug}" failed`, error);
            }
        }
    };

    renderAll();

    $(window).one('pagehide.chartsApex', () => {
        disposed = true;
        destroyAll();
    });
};

initializeDashboard('charts-apex', initializeApexCharts);
