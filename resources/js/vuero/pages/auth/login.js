import '../../../../css/admin-shell.css';
import { sendSettings, refreshPresentation } from '../../../admin/settings';
import { announce } from '../../../common/toast';
import '../../../../css/auth-login.css';
import { loadingButton, pageData } from '../../../common/page';
import $ from '../../admin/jquery';

$(() => {
    const $passwordToggle = $('[data-password-toggle]');

    $passwordToggle.on('mousedown', (event) => event.preventDefault());
    $passwordToggle.on('click', function () {
        const $passwordInput = $(this).closest('.form-field').find('[data-password-input]');
        const shouldReveal = $passwordInput.attr('type') === 'password';

        $passwordInput.attr('type', shouldReveal ? 'text' : 'password');
        $(this)
            .attr('aria-pressed', String(shouldReveal))
            .attr('aria-label', shouldReveal ? pageData().auth.hide_password : pageData().auth.show_password);
        $passwordInput.trigger('focus');
    });
});

document.querySelectorAll('.auth-shell form:not([data-auth-language])').forEach((form) => form.addEventListener('submit', () => { form.querySelectorAll('[type="submit"]').forEach(loadingButton); }));

const languageForm = document.querySelector('[data-auth-language]');
let changingLanguage = false;
languageForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (changingLanguage || !event.submitter) return;
    changingLanguage = true;
    const data = new FormData(languageForm);
    data.set('locale', event.submitter.value);
    const restore = loadingButton(event.submitter);
    const buttons = [...languageForm.querySelectorAll('button')];
    buttons.forEach((button) => { button.disabled = true; });
    try {
        const result = await sendSettings(languageForm.action, data);
        restore();
        buttons.forEach((button) => { button.disabled = true; });
        await refreshPresentation('[data-auth-shell]');
        document.querySelectorAll('[data-password-input]').forEach(input => { input.type = 'password'; });
        document.querySelectorAll('[data-password-toggle]').forEach(toggle => {
            toggle.setAttribute('aria-pressed', 'false');
            toggle.setAttribute('aria-label', pageData().auth.show_password);
        });
        document.querySelector('#languages-panel [data-panel-close]').click();
        announce(result.message);
    } catch (error) {
        restore();
        announce(error.status ? error.message : pageData().ui.save_failed, true);
    } finally {
        buttons.forEach((button) => { button.disabled = false; });
        changingLanguage = false;
    }
});

const universe = document.querySelector('[data-login-universe]');
if (universe) {
    const starfield = universe.querySelector('[data-starfield]');
    for (let index = 0; index < 96; index++) {
        const star = document.createElement('i');
        star.className = 'login-space-star';
        star.style.setProperty('--star-x', `${(index * 37.73 + 9) % 100}%`);
        star.style.setProperty('--star-y', `${(index * 23.17 + 7) % 100}%`);
        star.style.setProperty('--star-size', `${index % 4 === 0 ? 9 : 2 + index % 3}px`);
        star.style.setProperty('--star-opacity', String(.2 + (index % 5) * .1));
        star.style.setProperty('--depth', String(.4 + (index % 4) * .5));
        star.style.setProperty('--star-delay', `${-index % 7}s`);
        starfield.append(star);
    }
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const desktop = window.matchMedia('(min-width: 901px) and (pointer: fine)');
    let frame = 0;
    let x = 0;
    let y = 0;
    const paint = () => {
        frame = 0;
        universe.style.setProperty('--space-x', `${x * 18}px`);
        universe.style.setProperty('--space-y', `${y * 15}px`);
        universe.style.setProperty('--card-rx', `${-y * 3}deg`);
        universe.style.setProperty('--card-ry', `${x * 4}deg`);
    };
    const reset = () => { cancelAnimationFrame(frame); x = 0; y = 0; paint(); };
    universe.addEventListener('pointermove', (event) => {
        if (motion.matches || !desktop.matches) return;
        const box = universe.getBoundingClientRect();
        x = (event.clientX - box.left) / box.width * 2 - 1;
        y = (event.clientY - box.top) / box.height * 2 - 1;
        if (!frame) frame = requestAnimationFrame(paint);
    });
    universe.addEventListener('pointerleave', reset);
    motion.addEventListener('change', reset);
    desktop.addEventListener('change', reset);
}

// This palette only previews the illustration; it never changes saved preferences.
document.querySelectorAll('[data-creative-palette]').forEach((button) => {
    button.addEventListener('click', () => {
        const stage = button.closest('[data-creative-stage]');
        stage.dataset.palette = button.dataset.creativePalette;
        stage.querySelectorAll('[data-creative-palette]').forEach((swatch) => {
            swatch.setAttribute('aria-pressed', String(swatch === button));
        });
    });
});
