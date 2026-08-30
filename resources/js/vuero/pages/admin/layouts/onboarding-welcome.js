import $ from '../../../admin/jquery';
import { initializeOnboarding } from './projects-runtime';

$(() => {
    const $view = $('[data-onboarding-page]');
    if (!$view.length) return;
    initializeOnboarding($view);
});
