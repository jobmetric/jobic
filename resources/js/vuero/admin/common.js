import $ from './jquery';

const themeStorageKey = 'jobic-theme';
const languageStorageKey = 'jobic-locale';
const layoutStorageKey = 'jobic-layout';
const legacyLayouts = {
    'navdrop-default': 'navbar-dropdown',
    'navdrop-colored': 'navbar-dropdown-colored',
    'search-default': 'navbar-clean',
    'search-center': 'navbar-clean-center',
    'search-fade': 'navbar-clean-fade',
};

$(() => {
    const $document = $(document);
    const $body = $('body');
    const $root = $('html');
    const $shell = $('[data-admin-shell]');
    const $circularMenu = $('[data-circular-menu]');
    const mobileQuery = window.matchMedia('(max-width: 820px)');
    let activeMobilePanel = String($('[data-sidebar-panel-target].is-active').first().data('sidebar-panel-target') || 'dashboard');
    let lastPanelTrigger = null;
    let lastModalTrigger = null;
    let toastTimer = null;
    let circularScrollFrame = null;

    $root.attr(
        'data-theme',
        localStorage.getItem(themeStorageKey)
            || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
    );

    const updateThemeControls = () => {
        const isDark = $root.attr('data-theme') === 'dark';

        $('[data-theme-toggle]').attr('aria-pressed', String(isDark));
    };

    const setCircularMenuOpen = (isOpen) => {
        if (!$circularMenu.length) return;

        $circularMenu.toggleClass('is-open', isOpen);
        $circularMenu.find('[data-circular-menu-toggle]')
            .attr('aria-expanded', String(isOpen))
            .attr('aria-label', isOpen ? 'Close quick actions' : 'Open quick actions');
        $circularMenu.find('[data-circular-menu-items]')
            .attr('aria-hidden', String(!isOpen))
            .prop('inert', !isOpen);
    };

    const syncCircularMenu = () => {
        if (!$circularMenu.length) return;

        const isVisible = window.scrollY > 30;
        $circularMenu.toggleClass('is-active', isVisible);
        if (!isVisible) setCircularMenuOpen(false);
    };

    const closeDropdowns = ($except = $()) => {
        $('[data-dropdown].is-open').not($except).each(function () {
            const $dropdown = $(this);

            $dropdown.removeClass('is-open');
            $dropdown.find('[data-dropdown-toggle]').attr('aria-expanded', 'false');
            $dropdown.find('[data-dropdown-menu]').prop('hidden', true);
        });
    };

    const closeDesktopNavbarMenus = () => {
        $('[data-navdrop]').removeClass('is-open');
        $('[data-navdrop-toggle]').attr('aria-expanded', 'false');
        $('[data-navdrop-menu]').prop('hidden', true);
        $('[data-navbar-subnav-target]').removeClass('is-active').attr('aria-expanded', 'false');
        $('[data-navbar-subnav-target="home"]').addClass('is-active');
        $('[data-navbar-subnav]').prop('hidden', true);
        $('[data-navbar-inline-search]').prop('hidden', true);
        $('[data-navbar-search-toggle]').attr('aria-expanded', 'false');
    };

    const syncMobileNavigation = () => {
        const isMobile = mobileQuery.matches;
        const isOpen = $shell.hasClass('is-sidebar-open');
        const $navigation = $('[data-mobile-navigation]');

        $navigation.prop('inert', isMobile && !isOpen);
        if (isMobile) {
            $navigation.attr('aria-hidden', String(!isOpen));
        } else {
            $navigation.removeAttr('aria-hidden').prop('inert', false);
        }
    };

    const setMobileSidebar = (isOpen) => {
        if (!$shell.length) {
            return;
        }

        $shell.toggleClass('is-sidebar-open', isOpen);
        $('[data-sidebar-toggle]').attr('aria-expanded', String(isOpen));
        $body.toggleClass('has-navigation-overlay', isOpen);
        syncMobileNavigation();

        if (isOpen && mobileQuery.matches) {
            openSubsidebar(activeMobilePanel);
        } else if (!isOpen) {
            closeSubsidebar();
        }
    };

    const closeSubsidebar = () => {
        if (!$shell.length) {
            return;
        }

        $shell.removeClass('is-subsidebar-open').removeAttr('data-active-sidebar-panel');
        $('[data-subsidebar]').attr('aria-hidden', 'true').prop('inert', true);
        $('[data-sideblock]').attr('aria-hidden', 'true').prop('inert', true);
        $('[data-sidebar-panel-target]').attr('aria-expanded', 'false').removeClass('is-panel-active');
        $('[data-desktop-sidebar-toggle]').attr('aria-expanded', 'false').find('.icon-box-toggle').removeClass('active');
        $('[data-sidebar-panel]').prop('hidden', true).removeClass('is-active');
    };

    const openSubsidebar = (panelId) => {
        const $target = $(`[data-sidebar-panel="${panelId}"]`);
        const $trigger = $(`[data-sidebar-panel-target="${panelId}"]`).first();

        if (!$target.length) {
            return;
        }

        const isSameOpen = $shell.hasClass('is-subsidebar-open')
            && $shell.attr('data-active-sidebar-panel') === panelId;

        if (isSameOpen && mobileQuery.matches) {
            return;
        }

        if (isSameOpen) {
            closeSubsidebar();
            return;
        }

        $('[data-sidebar-panel]').prop('hidden', true).removeClass('is-active');
        $('[data-sidebar-panel-target]').attr('aria-expanded', 'false').removeClass('is-panel-active');

        $target.prop('hidden', false).addClass('is-active');
        $(`[data-sidebar-panel-target="${panelId}"]`).attr('aria-expanded', 'true').addClass('is-panel-active');
        $('[data-desktop-sidebar-toggle]').attr('aria-expanded', 'true').find('.icon-box-toggle').addClass('active');
        $('[data-subsidebar-title]').text($trigger.attr('aria-label') || 'Dashboards');
        const isDesktopSideblock = !mobileQuery.matches
            && String($shell.attr('data-selected-layout') || '').startsWith('sideblock-');

        $('[data-subsidebar]')
            .attr('aria-hidden', isDesktopSideblock ? 'true' : 'false')
            .prop('inert', isDesktopSideblock);
        $('[data-sideblock]')
            .attr('aria-hidden', isDesktopSideblock ? 'false' : 'true')
            .prop('inert', !isDesktopSideblock);
        $shell.addClass('is-subsidebar-open').attr('data-active-sidebar-panel', panelId);
        activeMobilePanel = panelId;

        if (mobileQuery.matches) {
            $shell.addClass('is-sidebar-open');
            $('[data-sidebar-toggle]').attr('aria-expanded', 'true');
            $body.addClass('has-navigation-overlay');
            syncMobileNavigation();
        }
    };

    const closeSidePanels = (restoreFocus = true) => {
        $('[data-side-panel].is-open').removeClass('is-open').attr('aria-hidden', 'true');
        $('[data-panel-open]').attr('aria-expanded', 'false');
        $body.removeClass('has-side-panel');

        if (restoreFocus && lastPanelTrigger) {
            $(lastPanelTrigger).trigger('focus');
        }

        lastPanelTrigger = null;
    };

    const openSidePanel = (panelId, trigger) => {
        const $panel = $(`[data-side-panel="${panelId}"]`);

        if (!$panel.length) {
            return;
        }

        closeDropdowns();
        closeModals(false);
        closeSidePanels(false);
        setMobileSidebar(false);
        lastPanelTrigger = trigger;
        $panel.addClass('is-open').attr('aria-hidden', 'false');
        $(trigger).attr('aria-expanded', 'true');
        $body.addClass('has-side-panel');

        window.setTimeout(() => $panel.find('.side-panel').trigger('focus'), 180);
    };

    const closeModals = (restoreFocus = true) => {
        $('[data-modal].is-open').removeClass('is-open').attr('aria-hidden', 'true');
        $('[data-modal-open]').attr('aria-expanded', 'false');
        $body.removeClass('has-modal');

        if (restoreFocus && lastModalTrigger) {
            $(lastModalTrigger).trigger('focus');
        }

        lastModalTrigger = null;
    };

    const openModal = (modalId, trigger) => {
        const $modal = $(`[data-modal="${modalId}"]`);

        if (!$modal.length) {
            return;
        }

        closeDropdowns();
        closeSidePanels(false);
        closeModals(false);
        setMobileSidebar(false);
        lastModalTrigger = trigger;
        $modal.addClass('is-open').attr('aria-hidden', 'false');
        $(trigger).attr('aria-expanded', 'true');
        $body.addClass('has-modal');
        window.setTimeout(() => $modal.find('.vuero-modal').trigger('focus'), 160);
    };

    const selectLayout = (layoutId, shouldNotify = true) => {
        layoutId = legacyLayouts[layoutId] || layoutId;
        const $option = $(`[data-layout-option="${layoutId}"]`).first();

        if (!$option.length) {
            return;
        }

        const family = layoutId.startsWith('sidebar-')
            ? 'sidebar'
            : (layoutId.startsWith('sideblock-') ? 'sideblock' : 'navbar');
        const tabFamily = layoutId.startsWith('navbar-dropdown')
            ? 'navdrop'
            : (layoutId.startsWith('navbar-clean') ? 'search' : family);
        const tabId = `layout-${tabFamily}`;

        $('[data-layout-option]').removeClass('is-active');
        $option.addClass('is-active');
        $('[data-layout-option]').closest('[data-tab-content]').prop('hidden', true).removeClass('is-active');
        $option.closest('[data-tab-content]').prop('hidden', false).addClass('is-active');
        $('[data-tab-target^="layout-"]').removeClass('is-active').attr('aria-selected', 'false');
        $(`[data-tab-target="${tabId}"]`).addClass('is-active').attr('aria-selected', 'true');
        $shell.attr('data-selected-layout', layoutId);
        $('[data-desktop-sidebar-toggle]').attr(
            'aria-controls',
            family === 'sideblock' ? 'admin-sideblock' : 'admin-subsidebar',
        );

        if (layoutId.startsWith('sidebar-')) {
            $shell.attr('data-sidebar-theme', layoutId);
        } else {
            $shell.removeAttr('data-sidebar-theme');
        }

        localStorage.setItem(layoutStorageKey, layoutId);
        closeDesktopNavbarMenus();

        if (shouldNotify) {
            if (mobileQuery.matches) {
                setMobileSidebar(false);
            } else {
                closeSubsidebar();
            }

            showToast(`${$option.find('strong').text()} selected.`);
        }
    };

    const showToast = (message) => {
        const $toast = $('[data-ui-toast]');

        if (!$toast.length || !message) {
            return;
        }

        window.clearTimeout(toastTimer);
        $toast.find('[data-ui-toast-message]').text(message);
        $toast.prop('hidden', false);

        window.requestAnimationFrame(() => $toast.addClass('is-visible'));
        toastTimer = window.setTimeout(() => {
            $toast.removeClass('is-visible');
            window.setTimeout(() => $toast.prop('hidden', true), 220);
        }, 3200);
    };

    const selectLanguage = (locale, shouldNotify = true) => {
        const $option = $(`[data-language-option][data-locale="${locale}"]`).first();

        if (!$option.length) {
            return;
        }

        const label = $option.data('label');
        const flag = String($option.data('flag'));

        $('[data-language-option]').removeClass('is-active').attr('aria-checked', 'false');
        $option.addClass('is-active').attr('aria-checked', 'true');
        $('[data-language-current-flag]').each(function () {
            const className = this.className.replace(/country-flag--[^\s]+/g, '').trim();
            this.className = `${className} country-flag--${flag}`;
        });
        $('[data-language-current-label]').text(label);
        $root.attr('lang', locale);
        localStorage.setItem(languageStorageKey, locale);

        if (shouldNotify) {
            showToast(`${label} selected.`);
        }
    };

    $document.on('click', '[data-theme-toggle]', function () {
        const nextTheme = $root.attr('data-theme') === 'dark' ? 'light' : 'dark';

        $root.attr('data-theme', nextTheme);
        localStorage.setItem(themeStorageKey, nextTheme);
        updateThemeControls();
    });

    $document.on('click', '[data-circular-menu-toggle]', function (event) {
        event.preventDefault();
        event.stopPropagation();
        setCircularMenuOpen(!$circularMenu.hasClass('is-open'));
    });

    $(window).on('scroll.circularMenu', () => {
        if (circularScrollFrame) return;
        circularScrollFrame = window.requestAnimationFrame(() => {
            circularScrollFrame = null;
            syncCircularMenu();
        });
    });

    $document.on('click', '[data-dropdown-toggle]', function (event) {
        event.stopPropagation();

        const $dropdown = $(this).closest('[data-dropdown]');
        const willOpen = !$dropdown.hasClass('is-open');

        if (willOpen && mobileQuery.matches && $dropdown.closest('.admin-toolbar, .admin-mobile-navbar').length) {
            setMobileSidebar(false);
        }

        closeDropdowns($dropdown);
        $dropdown.toggleClass('is-open', willOpen);
        $(this).attr('aria-expanded', String(willOpen));
        $dropdown.find('[data-dropdown-menu]').prop('hidden', !willOpen);

        if (willOpen) {
            $dropdown.find('[role^="menu"] button, [role="menuitem"]').first().trigger('focus');
        }
    });

    $document.on('click', '[data-dropdown-menu]', (event) => event.stopPropagation());
    $document.on('click', (event) => {
        if (!$(event.target).closest('[data-circular-menu]').length) {
            setCircularMenuOpen(false);
        }
        closeDropdowns();
        $('[data-navdrop]').removeClass('is-open');
        $('[data-navdrop-toggle]').attr('aria-expanded', 'false');
        $('[data-navdrop-menu]').prop('hidden', true);
    });

    $document.on('click', '[data-navbar-subnav-target]', function (event) {
        event.stopPropagation();
        const panelId = String($(this).data('navbar-subnav-target'));
        const wasOpen = $(this).hasClass('is-active') && !$('[data-navbar-subnav]').prop('hidden');

        $('[data-navbar-inline-search]').prop('hidden', true);
        $('[data-navbar-search-toggle]').attr('aria-expanded', 'false');
        $('[data-navbar-subnav-target]').removeClass('is-active').attr('aria-expanded', 'false');
        $('[data-navbar-subnav-panel]').prop('hidden', true);

        if (wasOpen) {
            $('[data-navbar-subnav]').prop('hidden', true);
            return;
        }

        $(this).addClass('is-active').attr('aria-expanded', 'true');
        $(`[data-navbar-subnav-panel="${panelId}"]`).prop('hidden', false);
        $('[data-navbar-subnav]').prop('hidden', false);
    });

    $document.on('click', '[data-navbar-subnav]', (event) => event.stopPropagation());

    $document.on('click', '[data-navbar-search-toggle]', function (event) {
        event.stopPropagation();
        $('[data-navbar-subnav]').prop('hidden', true);
        $('[data-navbar-subnav-target]').removeClass('is-active').attr('aria-expanded', 'false');
        $('[data-navbar-inline-search]').prop('hidden', false).find('input').trigger('focus');
        $(this).attr('aria-expanded', 'true');
    });

    $document.on('click', '[data-navbar-search-close]', () => {
        $('[data-navbar-inline-search]').prop('hidden', true);
        $('[data-navbar-search-toggle]').attr('aria-expanded', 'false').trigger('focus');
    });

    $document.on('click', '[data-navbar-inline-search]', (event) => event.stopPropagation());

    $document.on('click', '[data-navdrop-toggle]', function (event) {
        event.stopPropagation();
        const $drop = $(this).closest('[data-navdrop]');
        const shouldOpen = !$drop.hasClass('is-open');

        $('[data-navdrop]').not($drop).removeClass('is-open').find('[data-navdrop-toggle]').attr('aria-expanded', 'false').end().find('[data-navdrop-menu]').prop('hidden', true);
        $drop.toggleClass('is-open', shouldOpen);
        $(this).attr('aria-expanded', String(shouldOpen));
        $drop.find('[data-navdrop-menu]').prop('hidden', !shouldOpen);
    });

    $document.on('click', '[data-navdrop-menu]', (event) => event.stopPropagation());

    $document.on('click', '[data-sidebar-toggle]', () => {
        closeDropdowns();
        closeDesktopNavbarMenus();
        closeSidePanels(false);
        closeModals(false);
        setMobileSidebar(!$shell.hasClass('is-sidebar-open'));
    });

    $document.on('click', '[data-sidebar-close]', () => setMobileSidebar(false));
    $document.on('click', '[data-subsidebar-close]', closeSubsidebar);
    $document.on('click', '[data-desktop-sidebar-toggle]', () => {
        if ($shell.hasClass('is-subsidebar-open')) {
            closeSubsidebar();
        } else {
            openSubsidebar(activeMobilePanel);
        }
    });
    $document.on('click', '[data-sidebar-panel-target]', function () {
        openSubsidebar(String($(this).data('sidebar-panel-target')));
    });

    $document.on('click', '[data-sideblock-group-toggle]', function () {
        const $button = $(this);
        const $group = $button.closest('[data-sideblock-group]');
        const shouldOpen = !$group.hasClass('is-open');

        $group.siblings('[data-sideblock-group]').removeClass('is-open')
            .find('[data-sideblock-group-toggle]').attr('aria-expanded', 'false')
            .end().find('.admin-sideblock__children').prop('hidden', true);
        $group.toggleClass('is-open', shouldOpen);
        $button.attr('aria-expanded', String(shouldOpen));
        $group.find('.admin-sideblock__children').first().prop('hidden', !shouldOpen);
    });

    $document.on('click', '.subsidebar-menu a[href="#"]', function (event) {
        event.preventDefault();
        showToast(`${$(this).text().trim()} is ready for its route.`);
    });

    $document.on('click', '.profile-dropdown a[href="#"]', function (event) {
        event.preventDefault();
        showToast(`${$(this).find('strong').text().trim()} is ready for its route.`);
        closeDropdowns();
    });

    $document.on('click', '.subsidebar-menu a:not([href="#"])', () => {
        if (mobileQuery.matches) {
            setMobileSidebar(false);
        }
    });

    $document.on('click', '[data-panel-open]', function () {
        openSidePanel(String($(this).data('panel-open')), this);
    });

    $document.on('click', '[data-panel-close]', () => closeSidePanels());

    $document.on('click', '[data-modal-open]', function () {
        openModal(String($(this).data('modal-open')), this);
    });

    $document.on('click', '[data-modal-close]', () => closeModals());

    $document.on('click', '[data-tab-target]', function () {
        const $tabs = $(this).closest('[data-tabs]');
        const tabId = String($(this).data('tab-target'));

        $tabs.find('[data-tab-target]').removeClass('is-active').attr('aria-selected', 'false');
        $(this).addClass('is-active').attr('aria-selected', 'true');
        $tabs.find('[data-tab-content]').prop('hidden', true).removeClass('is-active');
        $tabs.find(`[data-tab-content="${tabId}"]`).prop('hidden', false).addClass('is-active');
    });

    $document.on('click', '[data-accordion-toggle]', function () {
        const $accordion = $(this).closest('[data-accordion]');
        const isOpen = $accordion.hasClass('is-open');

        $accordion.toggleClass('is-open', !isOpen);
        $(this).attr('aria-expanded', String(!isOpen));
        $accordion.find('[data-accordion-content]').prop('hidden', isOpen);
    });

    $document.on('click', '[data-language-option]', function () {
        selectLanguage(String($(this).data('locale')));
        closeDropdowns();
    });

    $document.on('click', '[data-layout-option]', function () {
        selectLayout(String($(this).data('layout-option')));
        closeModals();
    });

    $document.on('input', '[data-global-search]', function () {
        const query = String($(this).val()).trim().toLowerCase();
        const $panel = $(this).closest('[data-side-panel]');
        const $items = $panel.find('[data-search-item]');

        $panel.find('[data-search-default]').prop('hidden', Boolean(query));
        $panel.find('[data-search-results]').prop('hidden', !query);

        if (!query) {
            $items.prop('hidden', false);
            $panel.find('[data-search-empty]').prop('hidden', true);
            return;
        }

        $items.each(function () {
            $(this).prop('hidden', !String($(this).data('search-text')).includes(query));
        });
        $panel.find('[data-search-empty]').prop('hidden', $items.filter(':visible').length > 0);
    });

    $document.on('click', '[data-notification-item]', function () {
        const notificationId = String($(this).data('notification-id'));
        const $matchingItems = $(`[data-notification-item][data-notification-id="${notificationId}"]`);

        $matchingItems.addClass('is-read').find('.notification-item__unread').attr('aria-hidden', 'true');

        if (!$('[data-notification-item]:not(.is-read)').length) {
            $('[data-notification-indicator]').prop('hidden', true);
        }
    });

    $document.on('click', '[data-notifications-read]', function () {
        $('[data-notification-item]').addClass('is-read');
        $('.notification-item__unread').attr('aria-hidden', 'true');
        $('[data-notification-indicator]').prop('hidden', true);
        showToast('All notifications marked as read.');
    });

    $document.on('click', '[data-ui-message]', function () {
        showToast(String($(this).data('ui-message')));
    });

    $document.on('keydown', (event) => {
        if (event.key === 'Tab') {
            const $layer = $('[data-modal].is-open, [data-side-panel].is-open').last();

            if ($layer.length) {
                const $focusable = $layer.find('button:not([disabled]), a[href], input:not([disabled]), [tabindex]:not([tabindex="-1"])').filter(':visible');
                const first = $focusable.get(0);
                const last = $focusable.get(-1);

                if (first && last && event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    $(last).trigger('focus');
                } else if (first && last && !event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    $(first).trigger('focus');
                }
            }

            return;
        }

        if (event.key !== 'Escape') {
            return;
        }

        if ($circularMenu.hasClass('is-open')) {
            setCircularMenuOpen(false);
            return;
        }

        closeDropdowns();
        closeDesktopNavbarMenus();

        if ($('[data-modal].is-open').length) {
            closeModals();
            return;
        }

        if ($('[data-side-panel].is-open').length) {
            closeSidePanels();
            return;
        }

        if ($shell.hasClass('is-subsidebar-open')) {
            if (mobileQuery.matches) {
                setMobileSidebar(false);
            } else {
                closeSubsidebar();
            }
            return;
        }

        setMobileSidebar(false);
    });

    $(mobileQuery).on('change', (event) => {
        if (!event.matches) {
            setMobileSidebar(false);
        } else {
            syncMobileNavigation();
        }
    });

    $('[data-subsidebar]').prop('inert', true);
    $('[data-sideblock]').prop('inert', true);
    syncMobileNavigation();
    selectLanguage(localStorage.getItem(languageStorageKey) || 'en', false);
    selectLayout(localStorage.getItem(layoutStorageKey) || 'sidebar-default', false);
    updateThemeControls();
    syncCircularMenu();
});
