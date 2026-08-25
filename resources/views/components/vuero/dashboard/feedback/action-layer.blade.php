<div class="dashboard-action-layer" data-dashboard-action-layer aria-hidden="true">
    <button class="dashboard-action-layer__backdrop" type="button" tabindex="-1" data-dashboard-action-close aria-label="Close dialog"></button>
    <section class="dashboard-action-dialog" role="dialog" aria-modal="true" aria-labelledby="dashboard-action-title" tabindex="-1">
        <header>
            <span class="dashboard-action-dialog__icon" data-dashboard-action-icon><x-vuero.icon name="activity" size="22" /></span>
            <div><small data-dashboard-action-eyebrow>Dashboard action</small><h2 id="dashboard-action-title" data-dashboard-action-title>Action</h2></div>
            <button type="button" data-dashboard-action-close aria-label="Close dialog"><x-vuero.icon name="x" size="18" /></button>
        </header>
        <div class="dashboard-action-dialog__body" data-dashboard-action-body></div>
        <footer>
            <button class="vuero-button" type="button" data-dashboard-action-close>Cancel</button>
            <button class="vuero-button is-primary" type="button" data-dashboard-action-confirm>Continue</button>
        </footer>
    </section>
</div>

<aside class="dashboard-action-drawer" data-dashboard-drawer aria-hidden="true" tabindex="-1">
    <header><div><small data-dashboard-drawer-eyebrow>Live overview</small><h2 data-dashboard-drawer-title>Report</h2></div><button type="button" data-dashboard-drawer-close aria-label="Close report"><x-vuero.icon name="x" size="18" /></button></header>
    <div class="dashboard-action-drawer__body" data-dashboard-drawer-body></div>
</aside>

<div class="dashboard-cursor-glow" aria-hidden="true" data-dashboard-cursor-glow></div>
<div class="sr-only" role="status" aria-live="polite" data-dashboard-live-region></div>
