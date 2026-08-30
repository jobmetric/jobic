import $ from '../../../admin/jquery';
import { initializeProjectTabs } from './projects-runtime';

$(() => {
    const $view = $('[data-project-details]');
    if (!$view.length) return;
    initializeProjectTabs($view);
});
