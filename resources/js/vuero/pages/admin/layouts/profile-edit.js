import { initFormMultiselects } from './form-layout-runtime';
import { initProfileLayout } from './profile-layout-runtime';
import $ from '../../../admin/jquery';

initProfileLayout('[data-profile-edit]');

$(() => {
    initFormMultiselects($('[data-profile-edit]'));
});
