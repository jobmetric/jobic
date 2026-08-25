@props(['messages'])

<section class="inbox-messages" data-inbox-messages aria-label="Messages">
    <header class="inbox-header inbox-messages__header">
        <div class="inbox-header__actions">
            <button type="button" class="inbox-action inbox-messages__mobile-menu" data-inbox-sidebar-open aria-label="Open mailbox navigation"><x-vuero.icon name="chevron-right" size="21" /></button>
            <button type="button" class="inbox-action inbox-check-all" data-inbox-check-all aria-label="Select all visible messages" aria-pressed="false"><x-vuero.icon name="check" size="17" /></button>
        </div>
        <div class="inbox-header__actions">
            <label class="inbox-search">
                <x-vuero.icon name="search" size="17" />
                <span class="sr-only">Search inbox</span>
                <input type="search" placeholder="Search Inbox..." data-inbox-search>
                <button type="button" data-inbox-search-clear aria-label="Clear search" hidden><x-vuero.icon name="x" size="14" /></button>
            </label>
            <div class="ui-dropdown inbox-dropdown" data-dropdown>
                <button type="button" class="inbox-action" data-dropdown-toggle aria-label="Message list options" aria-haspopup="menu" aria-expanded="false"><x-vuero.icon name="more" size="18" /></button>
                <div class="ui-dropdown__menu inbox-dropdown__menu" data-dropdown-menu role="menu" hidden>
                    <button type="button" role="menuitem" data-inbox-list-action="read"><x-vuero.icon name="check" size="16" /><span>Mark all as read</span></button>
                    <button type="button" role="menuitem" data-inbox-list-action="hide-read"><x-vuero.icon name="eye-off" size="16" /><span>Hide read</span></button>
                    <hr>
                    <button type="button" role="menuitem" data-inbox-list-action="date"><x-vuero.icon name="calendar" size="16" /><span>Sort by date</span></button>
                    <button type="button" role="menuitem" data-inbox-list-action="user"><x-vuero.icon name="user" size="16" /><span>Sort by user</span></button>
                </div>
            </div>
        </div>
    </header>

    <div class="inbox-message-list" data-inbox-message-list>
        @foreach ($messages as $message)
            <x-vuero.dashboard.apps.inbox.message-card :message="$message" :selected="$message['id'] === 1" />
        @endforeach
        <div class="inbox-list-empty" data-inbox-list-empty hidden>
            <x-vuero.icon name="mail" size="30" /><strong>No messages found</strong><span>Try another folder or search term.</span>
        </div>
    </div>
</section>
