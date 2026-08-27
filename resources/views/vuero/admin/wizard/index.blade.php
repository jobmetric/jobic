<x-vuero.layouts.wizard title="Project Wizard" script="resources/js/vuero/pages/admin/wizard.js">
    <div class="wizard-v1" data-project-wizard data-total-steps="{{ count($steps) }}">
        <x-vuero.wizard.navigation :steps="$steps" />

        <progress class="wizard-progress" max="100" value="12.5" data-wizard-progress aria-label="Project creation progress">12.5%</progress>

        <main class="wizard-v1-wrapper" aria-live="polite">
            <x-vuero.wizard.steps.project-type />
            <x-vuero.wizard.steps.project-info />
            <x-vuero.wizard.steps.project-details :customers="$customers" />
            <x-vuero.wizard.steps.project-files />
            <x-vuero.wizard.steps.project-team :teammates="$teammates" />
            <x-vuero.wizard.steps.project-tools :tools="$tools" />
            <x-vuero.wizard.steps.review />
            <x-vuero.wizard.steps.success />
        </main>

        <x-vuero.wizard.actions />
        <div class="wizard-toast" role="status" aria-live="polite" data-wizard-toast hidden></div>
    </div>
</x-vuero.layouts.wizard>
