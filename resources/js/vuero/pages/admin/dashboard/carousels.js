import $ from '../../../admin/jquery';
import { tns } from 'tiny-slider/src/tiny-slider';
import 'tiny-slider/dist/tiny-slider.css';

export const initializeCarousels = ($dashboard) => {
    const $cards = $dashboard.find('[data-bank-cards-carousel]');
    if (!$cards.length || $cards.data('slider-ready')) return null;

    $cards.data('slider-ready', true);

    const slider = tns({
        container: $cards.get(0),
        items: 1.9,
        slideBy: 1,
        controls: false,
        nav: true,
        mouseDrag: true,
        touch: true,
        loop: false,
        speed: 420,
        autoplay: false,
        autoplayButtonOutput: false,
        responsive: {
            320: { items: 1.7 },
            700: { items: 1.9 },
            900: { items: 1.9 },
        },
    });

    const $slides = $cards.children('[data-bank-card]');
    const selectCard = (index) => {
        const safeIndex = Math.max(0, Math.min(index, $slides.length - 1));
        $slides.removeClass('is-active').eq(safeIndex).addClass('is-active');
        $dashboard.trigger('banking:cardchange', [$slides.eq(safeIndex), safeIndex]);
    };

    $slides.on('click keydown', function (event) {
        if (event.type === 'keydown' && !['Enter', ' '].includes(event.key)) return;
        if (event.type === 'keydown') event.preventDefault();
        const index = $slides.index(this);
        slider.goTo(index);
        selectCard(index);
    });

    slider.events.on('indexChanged', (info) => {
        selectCard(info.index);
    });
    selectCard(0);

    return slider;
};
