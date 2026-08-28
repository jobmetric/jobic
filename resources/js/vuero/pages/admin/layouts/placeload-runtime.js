import $ from '../../../admin/jquery';

export const initPlaceloadPage = (selector) => {
    $(() => {
        const $root = $(selector);
        if (!$root.length) {
            return;
        }

        const announce = (message) => {
            const $toast = $('[data-ui-toast]');
            if (!$toast.length) {
                return;
            }

            $toast.find('[data-ui-toast-message]').text(message);
            $toast.prop('hidden', false);
            requestAnimationFrame(() => $toast.addClass('is-visible'));
            window.setTimeout(() => {
                $toast.removeClass('is-visible');
                window.setTimeout(() => $toast.prop('hidden', true), 220);
            }, 2600);
        };

        $root.on('click', '[data-placeload-add]', () => announce('Add user dialog opened.'));

        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};
