import '../../css/crud.css';
import { pageData } from '../common/page';
import { sendSettings } from '../admin/settings';
import { announce } from '../common/toast';

const root = document.querySelector('[data-menu-list]');

root?.addEventListener('click', async event => {
    const button = event.target.closest('[data-delete-menu]');
    if (!button) return;

    const copy = pageData().menus;
    if (!window.confirm(copy.confirm_delete.replace(':name', button.dataset.menuName || copy.unnamed))) return;

    button.disabled = true;
    try {
        const data = new FormData();
        data.set('_method', 'DELETE');
        const result = await sendSettings(`${pageData().menuRoutes.index}/${Number(button.dataset.deleteMenu)}`, data);
        button.closest('[data-menu-row]')?.remove();
        announce(result.message);
    } catch (error) {
        button.disabled = false;
        announce(error.status ? error.message : copy.failed, true);
    }
});
