for (const carousel of document.querySelectorAll('[data-site-carousel]')) {
    const track = carousel.querySelector('[data-carousel-track]');
    const controls = carousel.querySelector('[data-carousel-controls]');
    if (!track || !controls) continue;

    const slides = [...track.children];
    const previous = controls.querySelector('[data-carousel-previous]');
    const next = controls.querySelector('[data-carousel-next]');
    const dots = [...controls.querySelectorAll('[data-carousel-dot]')];
    const autoplay = controls.querySelector('[data-carousel-autoplay]');
    const loop = carousel.dataset.loop === '1';
    const interval = Number(carousel.dataset.interval) * 1000;
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    const edge = getComputedStyle(track).direction === 'rtl' ? 'right' : 'left';
    const startOf = (node) => node.getBoundingClientRect()[edge];
    let index = 0;
    let playing = Boolean(autoplay) && !reducedMotion.matches;
    const setPlaying = (enabled) => {
        playing = enabled;
        autoplay?.setAttribute('aria-pressed', String(enabled));
    };
    const sync = () => {
        const start = startOf(track);
        index = slides.reduce((closest, slide, candidate) =>
            Math.abs(startOf(slide) - start) < Math.abs(startOf(slides[closest]) - start)
                ? candidate : closest, 0);
        if (previous) previous.disabled = !loop && index === 0;
        if (next) next.disabled = !loop && index === slides.length - 1;
        dots.forEach((dot, position) => dot.setAttribute('aria-current', String(position === index)));
    };
    const goTo = (position) => {
        const target = slides[position];
        if (!target) return;
        track.scrollBy({
            left: startOf(target) - startOf(track),
            behavior: reducedMotion.matches || Math.abs(position - index) > 1 ? 'instant' : 'smooth',
        });
    };
    const move = (offset) => {
        const position = index + offset;
        goTo(loop ? (position + slides.length) % slides.length : position);
    };

    previous?.addEventListener('click', () => move(-1));
    next?.addEventListener('click', () => move(1));
    dots.forEach((dot, position) => dot.addEventListener('click', () => goTo(position)));
    if (autoplay) {
        setPlaying(playing);
        autoplay.addEventListener('click', () => {
            if (!playing && !loop && index === slides.length - 1) goTo(0);
            setPlaying(!playing);
        });
        reducedMotion.addEventListener('change', () => {
            if (reducedMotion.matches) setPlaying(false);
        });
        setInterval(() => {
            if (!playing || document.hidden || carousel.matches(':hover, :focus-within')) return;
            if (!loop && index === slides.length - 1) {
                setPlaying(false);
                return;
            }
            move(1);
        }, interval);
    }
    track.addEventListener('scroll', sync, { passive: true });
    controls.hidden = false;
    sync();
}
