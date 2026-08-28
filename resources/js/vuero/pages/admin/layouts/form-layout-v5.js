import $ from '../../../admin/jquery';
import { initFormLayout, scrollToStep } from './form-layout-runtime';

initFormLayout('[data-form-layout-v5]', {
    submitMessage: 'Your shipment is successfully stored!',
    initialize: ($root, $form, announce) => {
        let currentStep = 0;
        let currentHelp = -1;
        let isLoading = false;

        const $sections = $form.find('[data-form-step]');
        const $mobileSteps = $form.find('[data-mobile-step]');
        const $sidebarSteps = $form.find('[data-sidebar-step]');
        const $helpContainer = $form.find('[data-help-container]');
        const $helpPanels = $form.find('[data-help-panel]');
        const $continue = $form.find('[data-step-continue]');
        const $stepsList = $form.find('[data-stepper-list]');

        const syncSteps = () => {
            $sections.each(function () {
                const step = Number($(this).data('form-step'));
                $(this).toggleClass('is-active', step <= currentStep);
            });

            $mobileSteps.each(function () {
                const step = Number($(this).data('mobile-step'));
                $(this).toggleClass('is-active', step === currentStep);
            });

            $sidebarSteps.each(function () {
                const step = Number($(this).data('sidebar-step'));
                $(this).toggleClass('is-active', step === currentStep);
            });

            $continue.text(currentStep === 4 ? 'Finish' : 'Continue');
            $continue.prop('disabled', isLoading);
            $continue.toggleClass('is-loading', isLoading);
        };

        const showHelp = (index) => {
            currentHelp = index;

            if (index === -1) {
                $stepsList.prop('hidden', false);
                $helpContainer.prop('hidden', true);
                $helpPanels.removeClass('is-active');
                return;
            }

            $stepsList.prop('hidden', true);
            $helpContainer.prop('hidden', false);
            $helpPanels.removeClass('is-active');
            $helpPanels.filter(`[data-help-panel="${index}"]`).addClass('is-active');
        };

        $form.on('click', '[data-help-toggle]', function () {
            const index = Number($(this).data('help-toggle'));
            showHelp(currentHelp === index ? -1 : index);
        });

        $form.on('click', '[data-help-close]', () => showHelp(-1));

        $form.on('click keydown', '[data-sidebar-step]', function (event) {
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            if (event.type === 'keydown') {
                event.preventDefault();
            }

            const step = Number($(this).data('sidebar-step'));
            if (step <= currentStep) {
                currentStep = step;
                syncSteps();
                scrollToStep(`#form-step-${step}`);
            }
        });

        $form.on('click', '[data-mobile-step-link]', function (event) {
            event.preventDefault();
            const step = Number($(this).closest('[data-mobile-step]').data('mobile-step'));
            if (step <= currentStep) {
                scrollToStep(`#form-step-${step}`, -150);
            }
        });

        $form.on('click', '[data-output-remove]', function () {
            $(this).closest('[data-output-item]').remove();
            announce('Item removed.');
        });

        $form.on('click', '[data-add-shipment]', () => announce('Shipment added to the list.'));

        $form.off('submit').on('submit', async (event) => {
            event.preventDefault();
            if (isLoading) {
                return;
            }

            isLoading = true;
            syncSteps();

            if (currentStep === 4) {
                announce('Your shipment is successfully stored!');
                await new Promise((resolve) => window.setTimeout(resolve, 1000));
                window.location.href = $root.data('dashboard-url');
                return;
            }

            await new Promise((resolve) => window.setTimeout(resolve, 400));
            currentStep += 1;
            isLoading = false;
            syncSteps();
            scrollToStep(`#form-step-${currentStep}`);
        });

        syncSteps();
    },
});
