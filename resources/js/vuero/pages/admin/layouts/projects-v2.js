import $ from '../../../admin/jquery';
import { initializeProjectsToolbar } from './projects-runtime';

$(() => {
    const $view = $('[data-projects-v2]');
    if (!$view.length) return;
    initializeProjectsToolbar($view);
});
