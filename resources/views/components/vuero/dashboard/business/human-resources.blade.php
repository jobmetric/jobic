@php
    $rookies = [
        ['avatar' => 13, 'name' => 'Tara Svenson', 'role' => 'UI/UX Designer', 'action' => 'Hire', 'badge' => 'illustrator'],
        ['avatar' => 37, 'name' => 'Helmut Fritz', 'role' => 'Product Manager', 'action' => 'Assess', 'badge' => 'france'],
        ['avatar' => 25, 'name' => 'Melany Wallace', 'role' => 'Web Developer', 'action' => 'Assess', 'badge' => 'javascript'],
    ];
    $todos = [
        ['title' => 'Call Mike R.', 'time' => 'at 8:30am', 'mine' => false, 'done' => true],
        ['title' => 'Finish document review', 'time' => 'at 11:30am', 'mine' => true, 'done' => false],
        ['title' => 'Project Meeting', 'time' => 'at 12:00pm', 'mine' => false, 'done' => true],
        ['title' => 'Meet with Kacey M.', 'time' => 'at 3:00pm', 'mine' => true, 'done' => false],
        ['title' => 'Review final draft', 'time' => 'at 6:00pm', 'mine' => true, 'done' => false],
    ];
@endphp

<div class="business-dashboard hr-dashboard" data-business-dashboard="human-resources">
    <div class="hr-page-grid">
        <main class="hr-main-column">
            <section class="hr-hero">
                <div class="hr-hero__user"><img src="{{ asset('vuero/images/admin/avatars/vuero-1.svg') }}" alt="Erik"><h1>Welcome back,<br>Erik.</h1></div>
                <div class="hr-hero__rookies"><h2>New Rookies</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praeclarae mortes.</p><div class="business-avatar-stack"><img src="{{ asset('vuero/images/admin/business/avatars/13.svg') }}" alt=""><img src="{{ asset('vuero/images/admin/business/avatars/32.svg') }}" alt=""><img src="{{ asset('vuero/images/admin/business/avatars/9.svg') }}" alt=""><button type="button" data-rookie-add aria-label="Add candidate"><x-vuero.icon name="plus" size="18" /></button></div></div>
                <div class="hr-hero__jobs"><h2>Job Feed</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praeclarae mortes.</p><button type="button" class="vuero-button" data-hr-manage-jobs>Manage Jobs</button></div>
            </section>

            <section class="hr-feed-settings">
                <h2>Manage feed settings</h2>
                <div role="tablist" data-hr-feed-tabs><button type="button" role="tab">All</button><button type="button" role="tab" class="is-active">Candidates</button><button type="button" role="tab">Companies</button></div>
            </section>

            <section class="hr-incoming-grid">
                <div class="hr-side-copy"><h2>More Details</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Perge porro; Oratio me istius philosophi non offendit.</p><button type="button" class="business-action-link" data-hr-read-more>Read More</button></div>
                <div class="hr-incoming-table">
                    <article data-feed-item="candidates"><span class="business-icon-box is-green"><x-vuero.icon name="mail" size="22" /></span><p><strong>Messages</strong><small>Inbox messages</small></p><b>5</b><i class="business-status is-online">New</i><button type="button" class="business-action-link" data-hr-open>Open</button></article>
                    <article data-feed-item="companies"><span class="business-icon-box is-orange"><x-vuero.icon name="check-circle" size="22" /></span><p><strong>Tasks</strong><small>Pending tasks</small></p><b>3</b><i class="business-status">Pending</i><button type="button" class="business-action-link" data-hr-open>Open</button></article>
                </div>
            </section>

            <section class="hr-rookies-section">
                <header class="business-card-heading"><h2>Recent Rookies</h2><button type="button" class="business-action-link" data-rookies-view-all>View All</button></header>
                <div class="hr-rookie-grid">
                    @foreach ($rookies as $rookie)
                        <article class="dashboard-card hr-rookie-card" data-rookie data-search="{{ strtolower($rookie['name'].' '.$rookie['role']) }}">
                            <button type="button" class="business-more-button" data-business-menu aria-label="Candidate options"><x-vuero.icon name="more" size="18" /></button>
                            <div class="business-avatar business-avatar--lg"><img src="{{ asset('vuero/images/admin/business/avatars/'.$rookie['avatar'].'.svg') }}" alt="{{ $rookie['name'] }}"><span class="business-avatar__badge">{{ substr($rookie['badge'], 0, 1) }}</span></div>
                            <h3>{{ $rookie['name'] }}</h3><p>{{ $rookie['role'] }}</p>
                            <button type="button" class="vuero-button @if($loop->first) is-primary @endif" data-rookie-action>{{ $rookie['action'] }}</button>
                        </article>
                    @endforeach
                </div>
                <p class="business-empty-state" data-rookie-empty hidden>No candidates match your search.</p>
            </section>
        </main>

        <aside class="hr-sidebar">
            <section class="dashboard-card hr-search-widget"><label class="business-search-input"><input type="search" placeholder="Search..." data-rookie-search><x-vuero.icon name="search" size="18" /></label></section>
            <section class="dashboard-card hr-todo-widget">
                <header class="business-card-heading"><h2>Todo</h2><div role="tablist" data-todo-tabs><button type="button" role="tab" aria-selected="true" class="is-active" data-todo-tab="all">All</button><button type="button" role="tab" aria-selected="false" data-todo-tab="mine">Mine</button><i class="hr-todo-naver" aria-hidden="true"></i></div></header>
                <div class="todo-list hr-todo-list" data-hr-todos>
                    @foreach ($todos as $todo)
                        <label data-todo data-mine="{{ $todo['mine'] ? 'true' : 'false' }}" @class(['is-complete' => $todo['done']])>
                            <input type="checkbox" @checked($todo['done'])><i></i>
                            <span><strong>{{ $todo['title'] }}</strong><small>{{ $todo['time'] }}</small></span>
                            <em>Pending</em>
                        </label>
                    @endforeach
                </div>
            </section>
            <section class="dashboard-card hr-calendar-widget"><x-vuero.dashboard.calendar /></section>
        </aside>
    </div>
</div>
