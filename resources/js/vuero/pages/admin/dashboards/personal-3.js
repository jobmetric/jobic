import { initializeDashboard } from '../dashboard-runtime';

const initializePersonalV3 = ($dashboard) => {
    const $progress = $dashboard.find('[data-personal-progress]');
    const $progressValue = $progress.find('[data-personal-progress-value]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const sequence = reducedMotion ? [100] : [10, 50, 70, 0, 100];

    sequence.forEach((value, index) => {
        window.setTimeout(() => {
            $progress.css('--progress-angle', `${value / 2}%`);
            $progressValue.text(`${value.toFixed(1)}%`);
        }, reducedMotion ? 0 : (index + 1) * 1000);
    });
};

initializeDashboard('personal-3', initializePersonalV3);
