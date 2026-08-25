import $ from '../../admin/jquery';

$(() => {
    const $passwordInput = $('[data-password-input]');
    const $passwordToggle = $('[data-password-toggle]');

    $passwordToggle.on('click', function () {
        const shouldReveal = $passwordInput.attr('type') === 'password';

        $passwordInput.attr('type', shouldReveal ? 'text' : 'password');
        $(this)
            .attr('aria-pressed', String(shouldReveal))
            .attr('aria-label', shouldReveal ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور');
        $passwordInput.trigger('focus');
    });
});
