import $ from '../../../admin/jquery';

const palettes = {
    primary: ['#2aac8c', '#76d7c2', '#3e8ed0'],
    purple: ['#797bf2', '#a594e8', '#2aac8c'],
    orange: ['#ff9a62', '#ffcf8a', '#2aac8c'],
    info: ['#24a8e0', '#77d1f0', '#797bf2'],
};

const areaSets = [
    [28, 39, 31, 52, 44, 67, 51, 78, 61, 73, 64, 86],
    [54, 41, 48, 33, 45, 29, 58, 42, 66, 53, 71, 62],
    [19, 32, 28, 46, 39, 54, 48, 61, 57, 73, 69, 81],
];

const barSets = [
    [38, 62, 47, 81, 56, 72, 43, 91, 67, 84, 52, 76],
    [24, 41, 53, 45, 69, 61, 74, 58, 82, 71, 90, 79],
    [16, 31, 27, 39, 35, 48, 43, 59, 52, 66, 61, 73],
];

const bankingHistorySets = [
    [318, 150, 49, 152.13, 421, 1009, 1220, 418, 113, 45],
    [192, 263, 459, 312, 349, 527, 397, 351, 618, 511],
];

const bankingExpenseSets = [
    [4, 3, 10, 9, 29, 19, 25, 9, 12, 7, 19, 5, 13, 9, 17, 2, 7, 5],
    [2, 3, 8, 7, 22, 16, 23, 7, 11, 5, 12, 5, 10, 4, 15, 2, 6, 2],
];

const bankingHistoryDates = [
    '2020-10-19T00:00:00.000Z', '2020-10-20T01:30:00.000Z',
    '2020-10-21T02:30:00.000Z', '2020-10-22T03:30:00.000Z',
    '2020-10-23T04:30:00.000Z', '2020-10-24T05:30:00.000Z',
    '2020-10-25T06:30:00.000Z', '2020-10-26T06:30:00.000Z',
    '2020-10-27T06:30:00.000Z', '2020-10-28T06:30:00.000Z',
];

const bankingExpenseDates = Array.from({ length: 18 }, (_, index) => (
    new Date(Date.UTC(2020, 10, 11 + index)).toISOString()
));

export const initializeCharts = ($dashboard) => {
    const instances = [];
    const $chartElements = $dashboard.find('[data-vuero-chart]');
    let pendingRange = null;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const styles = getComputedStyle(document.documentElement);
    const textColor = styles.getPropertyValue('--muted').trim() || '#a2a5b9';
    const borderColor = styles.getPropertyValue('--border').trim() || '#e5e5e5';

    const mountCharts = async () => {
        const { default: ApexCharts } = await import('apexcharts');

        $chartElements.each(function (index) {
            const element = this;
            const $chart = $(element);
            const type = String($chart.data('chart-type'));
            const tone = String($chart.data('chart-tone'));
            const height = Number($chart.data('chart-height')) || 190;
            const colors = palettes[tone] || palettes.primary;
            const isBankingHistory = $chart.closest('.banking-history-card').length > 0;
            const isBankingExpenses = $chart.closest('.banking-monthly-chart').length > 0;
            const legendCount = Math.max(1, $chart.siblings('.chart-legend').find('span').length || $chart.parent().find('.chart-legend').first().find('span').length);
            const seriesCount = Math.min(legendCount, 3);
            const customKind = isBankingHistory ? 'banking-history' : isBankingExpenses ? 'banking-expenses' : null;
            const sourceSets = isBankingHistory ? bankingHistorySets : isBankingExpenses ? bankingExpenseSets : (type === 'bars' ? barSets : areaSets);
            const categories = isBankingHistory ? bankingHistoryDates : isBankingExpenses ? bankingExpenseDates : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const xaxisLabels = { show: height >= 220, datetimeUTC: true, style: { colors: textColor, fontSize: '9px' } };
            const tooltip = { theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light' };
            if (isBankingExpenses) {
                xaxisLabels.format = 'dd MMM';
                tooltip.x = { format: 'dd MMM yyyy' };
            } else if (isBankingHistory) {
                tooltip.x = { format: 'dd/MM/yy HH:mm' };
                tooltip.y = { formatter: (value) => `$${value}` };
            }
            const options = {
            chart: {
                type: type === 'bars' ? 'bar' : 'area',
                height,
                fontFamily: 'Roboto, sans-serif',
                stacked: isBankingExpenses,
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: {
                    enabled: !reducedMotion,
                    easing: 'easeinout',
                    speed: 900,
                    animateGradually: { enabled: true, delay: 90 },
                    dynamicAnimation: { enabled: true, speed: 450 },
                },
                parentHeightOffset: 0,
                sparkline: { enabled: false },
            },
            colors: isBankingHistory ? palettes.primary.slice(0, 2) : isBankingExpenses ? [palettes.primary[0], palettes.purple[0]] : colors,
            series: Array.from({ length: seriesCount }, (_, seriesIndex) => ({
                name: isBankingHistory
                    ? ['Expenses', 'Earnings'][seriesIndex]
                    : isBankingExpenses ? ['Cash Expenses', 'Card Expenses'][seriesIndex] : ['Primary', 'Secondary', 'Growth'][seriesIndex],
                data: sourceSets[seriesIndex % sourceSets.length],
            })),
            stroke: { curve: 'smooth', width: type === 'bars' ? 0 : isBankingExpenses ? 3 : 2, lineCap: 'round' },
            fill: type === 'bars'
                ? { opacity: 0.92 }
                : isBankingExpenses
                    ? { type: 'solid', opacity: 0.7 }
                    : { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 90, 100] } },
            plotOptions: { bar: { borderRadius: 3, columnWidth: '54%' } },
            dataLabels: { enabled: false },
            legend: { show: false },
            grid: isBankingExpenses
                ? { show: false, padding: { top: 8, right: 5, bottom: -4, left: -5 } }
                : { borderColor, strokeDashArray: 0, padding: { top: 8, right: 5, bottom: -4, left: 5 } },
            xaxis: {
                type: customKind ? 'datetime' : 'category',
                categories,
                labels: xaxisLabels,
                axisBorder: { show: false }, axisTicks: { show: false },
                tooltip: { enabled: false },
            },
            yaxis: { labels: { show: height >= 220, style: { colors: textColor, fontSize: '9px' } } },
            tooltip,
            };
            const apexTarget = element.querySelector('[data-chart-apex]') || element;
            const chart = new ApexCharts(apexTarget, options);
            let rendered = false;

            const render = () => {
                if (rendered) return;
                rendered = true;
                chart.render()
                    .then(() => $chart.addClass('is-apex-ready').removeClass('is-fallback').removeAttr('data-chart-error'))
                    .catch((error) => $chart.addClass('is-fallback').attr('data-chart-error', String(error?.message || error)));
            };

            if ('IntersectionObserver' in window && !reducedMotion) {
                const observer = new IntersectionObserver((entries) => {
                    if (!entries[0]?.isIntersecting) return;
                    render();
                    observer.disconnect();
                }, { threshold: 0.12 });
                observer.observe(element);
            } else render();

            instances.push({ chart, type, index, render, element, seriesCount, customKind, sourceSets });
        });

        if (pendingRange) updateForRange(pendingRange);
    };

    const updateForRange = (range) => {
        pendingRange = range;
        const shift = { Hour: 0, Day: 1, Week: 2, Month: 3, Year: 4 }[range] || 0;
        instances.forEach(({ chart, type, index, render, seriesCount, customKind, sourceSets }) => {
            render();
            const sets = sourceSets || (type === 'bars' ? barSets : areaSets);
            chart.updateSeries(Array.from({ length: seriesCount }, (_, seriesIndex) => ({
                name: `${range} ${seriesIndex + 1}`,
                data: sets[(customKind ? seriesIndex : index + shift + seriesIndex) % sets.length]
                    .map((value, point) => Math.max(customKind === 'banking-expenses' ? 0 : 5, value + ((point * shift) % 13) - (customKind ? 0 : 5))),
            })), true);
        });
    };

    if ($chartElements.length) {
        mountCharts().catch(() => $chartElements.addClass('is-fallback'));
    }

    return { instances, updateForRange };
};
