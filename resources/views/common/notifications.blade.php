<div class="ui-dropdown toolbar-notifications {{ $notificationClass ?? '' }}" data-dropdown>
    <button class="toolbar-button" type="button" data-dropdown-toggle aria-label="{{ __('admin.notifications') }}" aria-haspopup="menu" aria-expanded="false"><x-vuero.icon name="bell" size="21" /></button>
    <div class="ui-dropdown__menu notification-dropdown__menu" role="menu" data-dropdown-menu hidden>
        <div class="notification-dropdown__head"><strong>{{ __('admin.notifications') }}</strong></div>
        <div class="cms-panel-empty"><x-vuero.icon name="check" size="30" /><p>{{ __('admin.no_notifications') }}</p></div>
    </div>
</div>
