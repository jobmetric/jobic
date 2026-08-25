@props(['placement' => 'desktop'])

<div {{ $attributes->class(['ui-dropdown', 'toolbar-notifications', 'toolbar-notifications--'.$placement]) }} data-dropdown>
    <button class="toolbar-button" type="button" data-dropdown-toggle aria-label="Notifications" aria-haspopup="menu" aria-expanded="false">
        <x-vuero.icon name="bell" size="21" />
        <span class="notification-dot" data-notification-indicator></span>
    </button>

    <div class="ui-dropdown__menu notification-dropdown__menu" role="menu" data-dropdown-menu hidden>
        <div class="notification-dropdown__head">
            <strong>NOTIFICATIONS</strong>
            <button type="button" data-notifications-read>SEE ALL</button>
        </div>
        <div class="notification-list">
            <button class="notification-item" type="button" role="menuitem" data-notification-item data-notification-id="alice-comment"><span class="notification-avatar">AC</span><span><strong>Alice C.</strong> left a comment.<small>1 hour ago</small></span></button>
            <button class="notification-item" type="button" role="menuitem" data-notification-item data-notification-id="joshua-file"><span class="notification-avatar is-orange">JS</span><span><strong>Joshua S.</strong> uploaded a file.<small>2 hours ago</small></span></button>
            <button class="notification-item" type="button" role="menuitem" data-notification-item data-notification-id="tara-message"><span class="notification-avatar is-purple">TS</span><span><strong>Tara S.</strong> sent you a message.<small>2 hours ago</small></span></button>
            <button class="notification-item is-read" type="button" role="menuitem" data-notification-item data-notification-id="melany-comment"><span class="notification-avatar is-green">MW</span><span><strong>Melany W.</strong> left a comment.<small>3 hours ago</small></span></button>
        </div>
    </div>
</div>
