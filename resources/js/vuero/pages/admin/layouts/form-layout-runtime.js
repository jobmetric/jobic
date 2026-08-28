import $ from '../../../admin/jquery';

const closeMultiselect = ($select) => {
    $select.removeClass('is-open is-active');
    $select.find('[data-form-multiselect-trigger]').attr('aria-expanded', 'false');
    $select.find('[data-form-multiselect-dropdown]').prop('hidden', true).addClass('is-hidden');
    $select.find('[data-form-multiselect-option]').removeClass('is-pointed');
};

export const initFormMultiselects = ($root) => {
    $root.find('[data-form-multiselect]').each(function () {
        const $select = $(this);
        if ($select.data('multiselect-init')) {
            return;
        }

        $select.data('multiselect-init', true);

        const $trigger = $select.find('[data-form-multiselect-trigger]');
        const $dropdown = $select.find('[data-form-multiselect-dropdown]');
        const $placeholder = $select.find('[data-form-multiselect-placeholder]');
        const $single = $select.find('[data-form-multiselect-single]');
        const $label = $select.find('[data-form-multiselect-label]');
        const $icon = $select.find('[data-form-multiselect-icon]');
        const $hidden = $select.find('[data-form-multiselect-value]');
        const $options = $select.find('[data-form-multiselect-option]');
        const isImage = $select.hasClass('is-image-select');

        const open = () => {
            $root.find('[data-form-multiselect].is-open').not($select).each(function () {
                closeMultiselect($(this));
            });

            $select.addClass('is-open is-active');
            $trigger.attr('aria-expanded', 'true');
            $dropdown.prop('hidden', false).removeClass('is-hidden');
        };

        $trigger.on('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if ($select.hasClass('is-open')) {
                closeMultiselect($select);
                return;
            }

            open();
        });

        $options.on('click', function (event) {
            event.stopPropagation();

            const value = String($(this).data('value'));
            const label = String($(this).data('label'));
            const icon = $(this).data('icon') ? String($(this).data('icon')) : null;

            $options.removeClass('is-selected is-pointed').attr('aria-selected', 'false');
            $(this).addClass('is-selected').attr('aria-selected', 'true');

            $hidden.val(value);
            $placeholder.prop('hidden', true);
            $single.prop('hidden', false);
            $label.text(label);

            if (isImage && $icon.length) {
                if (icon) {
                    $icon.attr('src', icon).prop('hidden', false);
                } else {
                    $icon.prop('hidden', true);
                }
            }

            closeMultiselect($select);
        });

        $options.on('mouseenter', function () {
            $options.removeClass('is-pointed');
            $(this).addClass('is-pointed');
        });

        $select.on('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMultiselect($select);
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();

                if ($select.hasClass('is-open')) {
                    closeMultiselect($select);
                } else {
                    open();
                }
            }
        });
    });

    $(document).on('click.formMultiselect', (event) => {
        if ($(event.target).closest('[data-form-multiselect]').length) {
            return;
        }

        $root.find('[data-form-multiselect].is-open').each(function () {
            closeMultiselect($(this));
        });
    });
};

export const initFormLayout = (selector, options = {}) => {
    $(() => {
        const $root = $(selector);
        if (!$root.length) {
            return;
        }

        const $form = $root.find('[data-form-layout-form]');
        let toastTimer = null;

        const announce = (message) => {
            const $toast = $('[data-ui-toast]');
            if (!$toast.length) {
                return;
            }

            window.clearTimeout(toastTimer);
            $toast.find('[data-ui-toast-message]').text(message);
            $toast.prop('hidden', false);
            requestAnimationFrame(() => $toast.addClass('is-visible'));
            toastTimer = window.setTimeout(() => {
                $toast.removeClass('is-visible');
                window.setTimeout(() => $toast.prop('hidden', true), 220);
            }, 2800);
        };

        initFormMultiselects($root);

        const $header = $form.find('[data-form-header]');

        if ($header.length && !$header.data('stuck-init')) {
            $header.data('stuck-init', true);

            const headerEl = $header[0];
            const sentinel = document.createElement('div');
            sentinel.className = 'form-header-sentinel';
            sentinel.setAttribute('aria-hidden', 'true');
            headerEl.before(sentinel);

            const syncStuck = (isStuck) => {
                $header.toggleClass('is-stuck', isStuck);
            };

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(
                    ([entry]) => syncStuck(!entry.isIntersecting),
                    { threshold: 0 },
                );
                observer.observe(sentinel);
            } else {
                const updateStuck = () => syncStuck(window.scrollY > 30);
                $(window).on('scroll', updateStuck);
                $(window).on('resize', updateStuck);
                updateStuck();
            }
        }

        $form.on('submit', (event) => {
            event.preventDefault();
            announce(options.submitMessage ?? 'Form submitted successfully.');
        });

        $form.on('change', '.radio-pill input', function () {
            $(this).closest('.radio-pills').find('.radio-pill').removeClass('is-selected');
            $(this).closest('.radio-pill').addClass('is-selected');
        });

        $form.on('click', '[data-color-code]', function () {
            $form.find('[data-color-code]').removeClass('is-active').attr('aria-pressed', 'false');
            $(this).addClass('is-active').attr('aria-pressed', 'true');
        });

        $form.on('click', '[data-add-participant]', () => announce('Add participant dialog opened.'));
        $form.on('click', '[data-add-people]', () => announce('Add people dialog opened.'));
        $form.on('click', '[data-add-link]', (event) => {
            event.preventDefault();
            announce('Attachment picker opened.');
        });
        $form.on('click', '[data-add-notification]', (event) => {
            event.preventDefault();
            announce('Notification added.');
        });
        $form.on('click', '[data-input-button]', () => announce('Item added to the list.'));

        if (typeof options.initialize === 'function') {
            options.initialize($root, $form, announce);
        }

        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};

export const scrollToStep = (id, offset = -20) => {
    const target = document.querySelector(id);
    if (!target) {
        return;
    }

    const top = target.getBoundingClientRect().top + window.scrollY + offset;
    window.scrollTo({ top, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
};
