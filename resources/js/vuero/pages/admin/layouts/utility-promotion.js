import { initActionPage } from './layout-pages-runtime';

initActionPage('[data-utility-promotion]', {
    skipSelector: '[data-promotion-skip]',
    upgradeSelector: '[data-promotion-upgrade]',
    skipMessage: 'Promotion skipped.',
    upgradeMessage: 'Upgrade flow started.',
});
