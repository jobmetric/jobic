import {
    area,
    areaLineRange,
    areaSplineRange,
    areaStep,
    bar,
    bubble,
    donut,
    gauge,
    line,
    pie,
    radar,
    scatter,
    spline,
    step,
} from 'billboard.js';

const title = (text) => ({ text, position: 'left', padding: { bottom: 20, right: 20, top: 0, left: 20 } });
const layout = (text) => ({ size: { height: 280 }, padding: { bottom: 20 }, title: title(text), legend: { position: 'inset' } });

const iris = {
    setosa: [0.2, 0.2, 0.2, 0.2, 0.2, 0.4, 0.3, 0.2, 0.2, 0.1, 0.2, 0.2, 0.1, 0.1, 0.2, 0.4, 0.4, 0.3, 0.3, 0.3, 0.2, 0.4, 0.2, 0.5, 0.2, 0.2, 0.4, 0.2, 0.2, 0.2, 0.2, 0.4, 0.1, 0.2, 0.2, 0.2, 0.2, 0.1, 0.2, 0.2, 0.3, 0.3, 0.2, 0.6, 0.4, 0.3, 0.2, 0.2, 0.2, 0.2],
    versicolor: [1.4, 1.5, 1.5, 1.3, 1.5, 1.3, 1.6, 1, 1.3, 1.4, 1, 1.5, 1, 1.4, 1.3, 1.4, 1.5, 1, 1.5, 1.1, 1.8, 1.3, 1.5, 1.2, 1.3, 1.4, 1.4, 1.7, 1.5, 1, 1.1, 1, 1.2, 1.6, 1.5, 1.6, 1.5, 1.3, 1.3, 1.3, 1.2, 1.4, 1.2, 1, 1.3, 1.2, 1.3, 1.3, 1.1, 1.3],
    virginica: [2.5, 1.9, 2.1, 1.8, 2.2, 2.1, 1.7, 1.8, 1.8, 2.5, 2, 1.9, 2.1, 2, 2.4, 2.3, 1.8, 2.2, 2.3, 1.5, 2.3, 2, 2, 1.8, 2.1, 1.8, 1.8, 1.8, 2.1, 1.6, 1.9, 2, 2.2, 1.5, 1.4, 2.3, 2.4, 1.8, 1.8, 2.1, 2.4, 2.3, 1.9, 2.3, 2.5, 2.3, 1.9, 2, 2.3, 1.8],
    setosaX: [3.5, 3, 3.2, 3.1, 3.6, 3.9, 3.4, 3.4, 2.9, 3.1, 3.7, 3.4, 3, 3, 4, 4.4, 3.9, 3.5, 3.8, 3.8, 3.4, 3.7, 3.6, 3.3, 3.4, 3, 3.4, 3.5, 3.4, 3.2, 3.1, 3.4, 4.1, 4.2, 3.1, 3.2, 3.5, 3.6, 3, 3.4, 3.5, 2.3, 3.2, 3.5, 3.8, 3, 3.8, 3.2, 3.7, 3.3],
    versicolorX: [3.2, 3.2, 3.1, 2.3, 2.8, 2.8, 3.3, 2.4, 2.9, 2.7, 2, 3, 2.2, 2.9, 2.9, 3.1, 3, 2.7, 2.2, 2.5, 3.2, 2.8, 2.5, 2.8, 2.9, 3, 2.8, 3, 2.9, 2.6, 2.4, 2.4, 2.7, 2.7, 3, 3.4, 3.1, 2.3, 3, 2.5, 2.6, 3, 2.6, 2.3, 2.7, 3, 2.9, 2.9, 2.5, 2.8],
};

const irisColumns = () => [
    ['setosa', ...iris.setosa],
    ['versicolor', ...iris.versicolor],
    ['virginica', ...iris.virginica],
];

const schedule = (actions) => (chart) => actions.map(([delay, action]) => window.setTimeout(() => action(chart), delay));

export const buildBillboardChartConfigs = (theme) => {
    const colors = {
        data1: theme.accent,
        data2: theme.primary,
        data3: theme.info,
        data4: theme.purple,
        setosa: theme.orange,
        versicolor: theme.purple,
        virginica: theme.success,
        setosa_x: theme.accent,
        versicolor_x: theme.primary,
        virginica_x: theme.info,
    };

    return {
        line: {
            options: { ...layout('Line Chart'), data: { type: line(), columns: [['data1', 30, 200, 100, 400, 150, 250], ['data2', 50, 20, 10, 40, 15, 25]], colors } },
            onReady: schedule([[2000, (chart) => chart.load({ columns: [['data1', 230, 190, 300, 500, 300, 400]] })], [6500, (chart) => chart.load({ columns: [['data3', 130, 150, 200, 300, 200, 100]] })]]),
        },
        regions: {
            options: { ...layout('Line Chart with Regions'), data: { type: line(), columns: [['data1', 30, 200, 100, 400, 150, 250], ['data2', 50, 20, 10, 40, 15, 25]], colors, regions: { data1: [{ start: 1, end: 2, style: { dasharray: '6 2' } }, { start: 3, style: { dasharray: '2 3' } }], data2: [{ end: 3 }] } } },
        },
        area: {
            options: { ...layout('Area Chart'), data: { type: area(), columns: [['data1', 300, 350, 300, 0, 0, 0], ['data2', 130, 100, 140, 200, 150, 50]], colors, types: { data1: 'area', data2: 'area-spline' } } },
        },
        'area-range': {
            options: {
                ...layout('Area Range Chart'),
                data: { x: 'x', type: line(), columns: [['x', '2013-01-01', '2013-01-02', '2013-01-03', '2013-01-04', '2013-01-05', '2013-01-06'], ['data1', [150, 140, 110], [155, 130, 115], [160, 135, 120], [135, 120, 110], [180, 150, 130], [199, 160, 125]], ['data2', 130, 340, 200, 500, 250, 350]], colors, types: { data1: areaLineRange() } },
                axis: { x: { type: 'timeseries', tick: { format: '%Y-%m-%d' } } },
            },
            onReady: schedule([
                [1000, (chart) => chart.load({ columns: [['data3', [220, 215, 205], [240, 225, 215], [260, 235, 225], [280, 245, 235], [270, 255, 225], [240, 225, 215]]], types: { data3: areaSplineRange() } })],
                [1500, (chart) => chart.load({ columns: [['data4', { high: 155, low: 145, mid: 150 }, { high: 200, mid: 190, low: 150 }, { high: 230, mid: 215, low: 200 }, { high: 210, mid: 200, low: 180 }, { high: 220, mid: 210, low: 190 }, { high: 200, mid: 180, low: 160 }]], types: { data4: areaSplineRange() } })],
            ]),
        },
        bar: {
            options: { ...layout('Bar Chart'), data: { type: bar(), columns: [['data1', 30, 200, 100, 400, 150, 250], ['data2', 130, 100, 140, 200, 150, 50]], colors }, bar: { width: { ratio: .5 } } },
            onReady: schedule([[1000, (chart) => chart.load({ columns: [['data3', 130, -150, 200, 300, -200, 100]] })]]),
        },
        'stacked-bar': {
            options: { ...layout('Stacked Bar Chart'), data: { type: bar(), columns: [['data1', -30, 200, 200, 400, -150, 250], ['data2', 130, 100, -100, 200, -150, 50], ['data3', -230, 200, 200, -300, 250, 250]], colors, groups: [['data1', 'data2']] }, grid: { y: { lines: [{ value: 0 }] } } },
            onReady: schedule([[1000, (chart) => chart.groups([['data1', 'data2', 'data3']])], [1500, (chart) => chart.load({ columns: [['data4', 100, -50, 150, 200, -300, -100]] })], [2000, (chart) => chart.groups([['data1', 'data2', 'data3', 'data4']])]]),
        },
        step: {
            options: { ...layout('Step Chart'), data: { columns: [['data1', 300, 350, 300, 0, 0, 100], ['data2', 130, 100, 140, 200, 150, 50]], colors, types: { data1: step(), data2: areaStep() } } },
        },
        spline: {
            options: { ...layout('Spline Chart'), data: { columns: [['data1', 30, 200, 100, 400, 150, 250], ['data2', 130, 100, 140, 200, 150, 50]], colors, type: spline() } },
        },
        bubble: {
            options: { ...layout('Bubble Chart'), data: { columns: [['data1', 30, 190, 200, 110, 150, 160, 50, 80, 55, 220], ['data2', 130, 100, 10, 143, 80, 50, 200, 123, 185, 98], ['data3', 160, 153, 85, 80, 250, 120, 5, 84, 99, 175]], colors, type: bubble(), labels: true }, bubble: { maxR: 50 }, axis: { x: { type: 'category' }, y: { max: 450 } } },
            onReady: schedule([[1000, (chart) => chart.load({ columns: [['data1', 100, 50, 150, 200, 100, 350, 58, 210, 80, 126]] })], [2000, (chart) => chart.load({ columns: [['data2', 305, 350, 55, 25, 335, 29, 258, 310, 180, 226]] })], [3000, (chart) => chart.load({ columns: [['data3', 223, 121, 259, 247, 53, 159, 95, 111, 307, 337]] })]]),
        },
        scatter: {
            options: { ...layout('Scatter Plot'), data: { xs: { setosa: 'setosa_x', versicolor: 'versicolor_x' }, columns: [['setosa_x', ...iris.setosaX], ['versicolor_x', ...iris.versicolorX], ['setosa', ...iris.setosa], ['versicolor', ...iris.versicolor]], colors, type: scatter() }, axis: { x: { tick: { fit: false } } } },
            onReady: schedule([[1000, (chart) => chart.load({ xs: { virginica: 'virginica_x' }, columns: [['virginica_x', ...iris.setosaX.map((value, index) => +(value - .45 + (index % 4) * .08).toFixed(2))], ['virginica', ...iris.virginica]] })], [3500, (chart) => chart.unload({ ids: ['setosa', 'setosa_x'] })], [5500, (chart) => chart.load({ xs: { setosa: 'setosa_x' }, columns: [['setosa_x', ...iris.setosaX], ['setosa', ...iris.setosa]] })]]),
        },
        pie: {
            options: { ...layout('Pie Chart'), data: { columns: [['data1', 30], ['data2', 120]], colors, type: pie() } },
            onReady: schedule([[2000, (chart) => chart.load({ columns: irisColumns() })], [6500, (chart) => chart.unload({ ids: ['data1', 'data2'] })]]),
        },
        donut: {
            options: { ...layout('Donut Chart'), data: { columns: [['data1', 30], ['data2', 120]], colors, type: donut() }, donut: { title: 'Inner Title' } },
            onReady: schedule([[2000, (chart) => chart.load({ columns: irisColumns() })], [6500, (chart) => chart.unload({ ids: ['data1', 'data2'] })]]),
        },
        gauge: {
            options: { ...layout('Gauge Chart'), data: { columns: [['data', 91.4]], type: gauge() }, gauge: {}, color: { pattern: [theme.accent, theme.info, theme.primary, theme.purple], threshold: { values: [30, 60, 90, 100] } } },
            onReady: schedule([[1000, (chart) => chart.load({ columns: [['data', 10]] })], [2000, (chart) => chart.load({ columns: [['data', 50]] })], [3000, (chart) => chart.load({ columns: [['data', 70]] })], [4000, (chart) => chart.load({ columns: [['data', 0]] })], [5000, (chart) => chart.load({ columns: [['data', 100]] })]]),
        },
        radar: {
            options: { ...layout('Radar Chart'), data: { x: 'x', columns: [['x', 'Data A', 'Data B', 'Data C', 'Data D', 'Data E'], ['data1', 330, 350, 200, 380, 150], ['data2', 130, 100, 30, 200, 80], ['data3', 230, 153, 85, 300, 250]], colors, type: radar(), labels: true }, radar: { axis: { max: 400 }, level: { depth: 4 }, direction: { clockwise: true } } },
        },
    };
};
