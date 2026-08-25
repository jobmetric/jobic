import $ from '../../../admin/jquery';

const numericPattern = /(-?[$€£]?)([\d,.]+)(K|M|B|%|kcal|km|lbs)?/i;

const animateNumber = (element) => {
    const $element = $(element);
    if ($element.data('counted')) return;

    const original = $element.text().trim();
    const match = original.match(numericPattern);
    if (!match) return;

    const decimals = (match[2].split('.')[1] || '').length;
    const target = Number(match[2].replace(/,/g, ''));
    if (!Number.isFinite(target) || target === 0 || target > 999999999999) return;

    const prefix = match[1] || '';
    const suffix = match[3] || '';
    const start = performance.now();
    const duration = 850;
    $element.data('counted', true);

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - ((1 - progress) ** 3);
        const value = target * eased;
        const formatted = value.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
        $element.text(original.replace(match[0], `${prefix}${formatted}${suffix}`));
        if (progress < 1) window.requestAnimationFrame(tick);
        else $element.text(original);
    };

    window.requestAnimationFrame(tick);
};

export const initializeMotion = ($dashboard) => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const selectors = '.dashboard-card, .dashboard-stat, .food-hero, .health-summary, .writer-hero, .contest-card, .flight-search, .personal-welcome, .personal-review, .video-card, .restaurant-grid > article, .stock-graph-card, .stock-list-card, .stock-category';
    const $revealItems = $dashboard.find(selectors);
    const $glow = $('[data-dashboard-cursor-glow]');

    $('html').addClass('has-dashboard-motion');
    $revealItems.each(function (index) {
        this.style.setProperty('--reveal-order', index % 9);
    });

    if (reducedMotion || !('IntersectionObserver' in window)) {
        $revealItems.addClass('is-revealed');
        $dashboard.find('strong').not('.dashboard-calendar strong').each((_, element) => animateNumber(element));
    } else {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-revealed');
                $(entry.target).find('strong').addBack('strong').not('.dashboard-calendar strong').each((_, element) => animateNumber(element));
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -35px' });
        $revealItems.each((_, element) => observer.observe(element));
    }

    $dashboard.on('pointerdown.dashboardMotion', 'button, a', function (event) {
        const rect = this.getBoundingClientRect();
        const $ripple = $('<span class="dashboard-ripple" aria-hidden="true"></span>');
        $ripple.css({ left: event.clientX - rect.left, top: event.clientY - rect.top });
        $(this).append($ripple);
        window.setTimeout(() => $ripple.remove(), 650);
    });

    if (!reducedMotion && window.matchMedia('(pointer: fine)').matches) {
        let frame = null;
        $dashboard.on('pointermove.dashboardMotion', (event) => {
            if (frame) return;
            frame = window.requestAnimationFrame(() => {
                $glow.css({ transform: `translate3d(${event.clientX - 110}px,${event.clientY - 110}px,0)`, opacity: 1 });
                frame = null;
            });
        }).on('pointerleave.dashboardMotion', () => $glow.css('opacity', 0));
    }
};
