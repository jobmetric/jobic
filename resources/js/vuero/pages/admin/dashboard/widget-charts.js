import $ from '../../../admin/jquery';
import ApexCharts from 'apexcharts';
import { readChartTheme } from './chart-theme';
import { widgetChartConfigs } from './widget-chart-configs';

export const initializeWidgetCharts = ($page) => {
    let charts = [];
    let frame = null;
    let disposed = false;
    const render = async () => {
        charts.forEach((chart) => chart.destroy());
        charts = [];
        const configs = widgetChartConfigs(readChartTheme());
        for (const element of $page.find('[data-widget-chart]').get()) {
            element.replaceChildren();
            const config = configs[element.dataset.widgetChart];
            if (!config) continue;
            const chart = new ApexCharts(element, config);
            charts.push(chart);
            try { await chart.render(); } catch (error) { console.error(`[Vuero widgets] Chart ${element.dataset.widgetChart} failed`, error); }
        }
    };
    render();
    const observer = new MutationObserver(() => { window.cancelAnimationFrame(frame); frame = window.requestAnimationFrame(() => { if (!disposed) render(); }); });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    $(window).one('pagehide.widgetCharts', () => { disposed = true; observer.disconnect(); charts.forEach((chart) => chart.destroy()); });
};
