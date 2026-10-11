import { iconMarkup } from '../common/icons';

function initializeSocialNetworks() {
    document.querySelectorAll('[data-social-networks]').forEach((editor) => {
        if (editor.dataset.initialized) return;
        editor.dataset.initialized = 'true';
        const list = editor.querySelector('[data-social-network-list]');
        const template = editor.querySelector('[data-social-network-template]');
        let nextIndex = [...list.querySelectorAll('[name^="networks["]')].reduce((maximum, input) => {
            const match = input.name.match(/^networks\[(\d+)\]/);
            return Math.max(maximum, Number(match?.[1] || -1));
        }, -1) + 1;
        const update = (row) => {
            const select = row.querySelector('[data-social-network-type]');
            const slug = row.querySelector('[data-social-network-slug]');
            const option = select.options[select.selectedIndex];
            const hasNetwork = Boolean(option?.dataset.urlTemplate);
            row.querySelector('[data-social-network-slug-field]').hidden = !hasNetwork;
            const identifier = slug.value.trim().replace(/^@/, '');
            const url = hasNetwork && identifier ? option.dataset.urlTemplate.replace('{slug}', encodeURIComponent(identifier)) : '';
            const example = row.querySelector('[data-social-network-example]');
            example.hidden = !url;
            example.querySelector('[data-social-network-example-text]').textContent = url ? editor.dataset.addressExample.replace('{url}', url) : '';
            const icon = row.querySelector('[data-social-network-icon]');
            if (icon) icon.innerHTML = iconMarkup(option?.dataset.icon || 'share', 21);
        };
        const bind = (row) => {
            row.querySelector('[data-social-network-type]').addEventListener('change', () => update(row));
            row.querySelector('[data-social-network-slug]').addEventListener('input', () => update(row));
            row.querySelector('[data-social-network-remove]').addEventListener('click', () => row.remove());
            update(row);
        };
        list.querySelectorAll('[data-social-network-row]').forEach(bind);
        editor.querySelector('[data-social-network-add]').addEventListener('click', () => {
            const instance = document.createElement('template');
            instance.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
            const fragment = instance.content;
            const row = fragment.querySelector('[data-social-network-row]');
            list.append(fragment);
            bind(row);
            row.querySelector('[data-social-network-type]').focus();
        });
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeSocialNetworks);
else initializeSocialNetworks();
