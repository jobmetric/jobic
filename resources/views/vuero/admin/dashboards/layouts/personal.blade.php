@switch($dashboard['slug'])
    @case('personal-1')
        <header class="personal-welcome dashboard-card">
            <img src="{{ asset('vuero/images/avatars/svg/vuero-1.svg') }}" alt="Erik Kovalsky">
            <div><h1>Welcome back, Erik Kovalsky</h1><p>We're very happy to see you again on your personal dashboard.</p></div>
            <div class="dashboard-actions"><button class="vuero-button" type="button">View Reports</button><button class="vuero-button is-primary" type="button">Manage Store</button></div>
        </header>
        <div class="dashboard-grid dashboard-grid--2">
            <x-vuero.dashboard.card title="Your Quick Stats" class="quick-stat-card">
                <div class="quick-stat-grid">
                    <x-vuero.dashboard.stat label="Sales this month" value="2,870" tone="purple" icon="activity" />
                    <x-vuero.dashboard.stat label="New users" value="131" tone="orange" icon="user-plus" />
                    <x-vuero.dashboard.stat label="Earned today" value="$398,49" tone="green" icon="diamond" />
                    <x-vuero.dashboard.stat label="Total balance" value="$6542,31" tone="info" icon="credit-card" />
                </div>
            </x-vuero.dashboard.card>
            <x-vuero.dashboard.card class="customers-card">
                <div class="chart-legend"><span>Returning</span><span>Newcomers</span><span>Abandonned</span></div>
                <x-vuero.dashboard.chart height="230" />
            </x-vuero.dashboard.card>
        </div>
        <div class="dashboard-grid dashboard-grid--3 dashboard-grid--personal-bottom">
            <x-vuero.dashboard.card class="upgrade-card"><span class="upgrade-card__crown">♕</span><h2>Hey Erik, you're doing great.</h2><p>Start using our team and project management tools</p><button type="button">Learn More</button></x-vuero.dashboard.card>
            <x-vuero.dashboard.card title="Team Efficiency" class="gauge-card"><div class="dashboard-gauge" style="--progress:76"><span><b>76%</b>Efficiency</span></div><div class="avatar-row"><span>SC</span><span>EK</span><span>+5</span></div></x-vuero.dashboard.card>
            <x-vuero.dashboard.card title="Profit Evolution"><x-vuero.dashboard.chart type="bars" height="190" /><div class="chart-months"><span>May</span><span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span></div></x-vuero.dashboard.card>
        </div>
        @break

    @case('personal-2')
        <header class="personal-review dashboard-card">
            <img src="{{ asset('vuero/images/avatars/svg/vuero-1.svg') }}" alt="Erik Kovalsky">
            <div><h1>Welcome back,<br>Erik K.</h1><p>It's really nice to see you again</p></div>
            <div class="review-count"><strong>3</strong><span>Tasks are pending review</span><a href="#tasks">View Tasks</a></div>
            <div class="review-cta"><p>Start using our team and project management tools</p><strong>Learn More</strong><span>♕</span></div>
        </header>
        <div class="dashboard-grid dashboard-grid--personal-two">
            <div class="dashboard-stack">
                <x-vuero.dashboard.card title="Active Projects" action="View All">
                    <div class="project-list">
                        @foreach ([['AI','Delivery App Project','Updated 30m ago'],['R','Health and Fitness Dashboard','Updated 5h ago'],['A','Learning Tracker Dashboard','Updated 7h ago'],['JS','Banking App Dashboard','Updated 10h ago']] as $project)
                            <div><span>{{ $project[0] }}</span><p><strong>{{ $project[1] }}</strong><small>{{ $project[2] }}</small></p><button type="button">⋮</button></div>
                        @endforeach
                    </div>
                </x-vuero.dashboard.card>
                <x-vuero.dashboard.card title="Task Completion" action="Reports"><div class="chart-legend"><span>Pending</span><span>Completed</span><span>Blocked</span></div><x-vuero.dashboard.chart type="bars" /></x-vuero.dashboard.card>
            </div>
            <div class="dashboard-stack">
                <x-vuero.dashboard.card title="My Team" action="View All">
                    <div class="people-list">
                        @foreach ([['EC','Esteban C.'],['SC','Sara Connor'],['TS','Tara S.'],['NL','Naomi Liversky']] as $person)
                            <div><span>{{ $person[0] }}</span><p><strong>{{ $person[1] }}</strong><small>UI/UX Designer</small></p><i>☎ &nbsp; ▪</i></div>
                        @endforeach
                    </div>
                </x-vuero.dashboard.card>
                <x-vuero.dashboard.card title="Todo Today" action="View All"><div class="todo-list">@foreach (['Call Mr. Markstrom','Finish wireframes','Book team meeting'] as $todo)<label><input type="checkbox"> <span><strong>{{ $todo }}</strong><small>Review the project details</small></span></label>@endforeach</div></x-vuero.dashboard.card>
            </div>
        </div>
        @break

    @case('personal-3')
        <x-vuero.dashboard.personal.dashboard-v3 />
        @break
@endswitch
