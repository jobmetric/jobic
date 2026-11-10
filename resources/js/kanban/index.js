import {createApp} from 'vue';
import Board from './Board.vue';
import {enhanceSelects} from '../common/selects';
const root = document.querySelector('[data-kanban-root]');
if (root) createApp(Board).mount(root);
enhanceSelects(document);
