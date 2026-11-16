import {copy, showDialog} from './shared';

export function bindExamples(form) {
    form.addEventListener('click', async event => {
        const trigger = event.target.closest('[data-show-example]');
        if (!trigger || form.dataset.saving) return;
        const key = trigger.dataset.showExample;
        const template = form.querySelector(`[data-example-details="${CSS.escape(key)}"]`);
        const labels = copy().samples[key];
        if (!template || !labels) return;
        try {
            await showDialog({title: labels.title, description: labels.details,
                content: template.content.cloneNode(true)});
        } finally {
            trigger.focus({preventScroll: true});
        }
    });
}
