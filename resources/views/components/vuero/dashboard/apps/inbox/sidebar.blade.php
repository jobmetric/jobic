@props(['contacts'])

<aside class="inbox-sidebar" data-inbox-sidebar aria-label="Mailbox navigation">
    <header class="inbox-header inbox-sidebar__header">
        <div class="inbox-sidebar__title">
            <a href="{{ route('vuero.admin.dashboard') }}" class="inbox-sidebar__brand" aria-label="Dashboard"><x-vuero.brand compact /></a>
            <span>Inbox</span>
            <button type="button" class="inbox-theme-toggle theme-toggle" data-theme-toggle aria-label="Toggle dark mode" aria-pressed="false">
                <x-vuero.icon class="theme-toggle__sun" name="sun" size="20" />
                <x-vuero.icon class="theme-toggle__moon" name="moon" size="20" />
            </button>
        </div>

        <div class="ui-dropdown inbox-dropdown" data-dropdown>
            <button type="button" class="inbox-action" data-dropdown-toggle aria-label="Inbox options" aria-haspopup="menu" aria-expanded="false"><x-vuero.icon name="more" size="18" /></button>
            <div class="ui-dropdown__menu inbox-dropdown__menu" data-dropdown-menu role="menu" hidden>
                @foreach ([['refresh','refresh','Refresh'],['notifications','bell','Notifications'],['invite','user-plus','Invite People'],['settings','settings','Settings']] as $item)
                    <button type="button" role="menuitem" data-inbox-global-action="{{ $item[0] }}"><x-vuero.icon name="{{ $item[1] }}" size="16" /><span>{{ $item[2] }}</span></button>
                @endforeach
            </div>
        </div>

        <button type="button" class="inbox-action inbox-sidebar__close" data-inbox-sidebar-close aria-label="Close mailbox navigation"><x-vuero.icon name="x" size="19" /></button>
    </header>

    <div class="inbox-sidebar__inner">
        <div class="inbox-sidebar__menu">
            <button type="button" class="inbox-compose-button" data-inbox-compose-open>Compose</button>
            <nav class="inbox-folders" aria-label="Mail folders">
                @foreach ([
                    ['inbox','mail','Inbox','24'],
                    ['drafts','content','Drafts','2'],
                    ['sent','share','Sent','7'],
                    ['trash','trash','Trash','18'],
                    ['spam','shield','Spam','29'],
                ] as $folder)
                    <button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-inbox-folder="{{ $folder[0] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                        <x-vuero.icon name="{{ $folder[1] }}" size="17" /><span>{{ $folder[2] }}</span><b>{{ $folder[3] }}</b>
                    </button>
                @endforeach
            </nav>
        </div>

        <section class="inbox-contacts" aria-label="Contacts">
            <header>
                <h2 data-inbox-contact-title>Contacts</h2>
                <label data-inbox-contact-search-wrap hidden>
                    <x-vuero.icon name="search" size="15" />
                    <span class="sr-only">Search contacts</span>
                    <input type="search" placeholder="Search Contacts..." data-inbox-contact-search>
                </label>
                <button type="button" class="inbox-action" data-inbox-contact-search-open aria-label="Search contacts"><x-vuero.icon name="search" size="17" /></button>
                <button type="button" class="inbox-action" data-inbox-contact-search-close aria-label="Close contact search" hidden><x-vuero.icon name="x" size="17" /></button>
            </header>
            <div class="inbox-contact-list" data-inbox-contact-list>
                @foreach ($contacts as $contact)
                    <button type="button" class="inbox-contact" data-inbox-contact data-contact-name="{{ $contact['name'] }}" data-contact-email="{{ $contact['email'] }}">
                        <span class="inbox-avatar"><span>{{ collect(explode(' ', $contact['name']))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span><img data-avatar-src="https://media.cssninja.io/content/avatars/{{ $contact['avatar'] }}.jpg" alt="" hidden></span>
                        <span><strong>{{ $contact['name'] }}</strong><small>{{ $contact['email'] }}</small></span>
                    </button>
                @endforeach
                <p class="inbox-empty-copy" data-inbox-contact-empty hidden>No matching contact.</p>
            </div>
        </section>
    </div>
</aside>
