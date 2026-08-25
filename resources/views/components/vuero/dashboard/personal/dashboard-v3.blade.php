<div class="personal-dashboard-v3">
    <div class="interview-dashboard">
        <main class="interview-main">
            <section class="dashboard-card interview-copy">
                <div class="interview-copy__title">
                    <h1>Hi Erik K.</h1>
                    <p>You have 6 interviews to conduct during this week. Your current progress is great.</p>
                </div>

                <div class="mini-progress">
                    <div class="mini-progress__gauge" data-personal-progress style="--progress-angle: 45.7%">
                        <span data-personal-progress-value>91.4%</span>
                    </div>
                    <p>
                        <span>Your Progress</span>
                        <strong>Outstanding <span role="img" aria-label="Party Popper">🎉</span></strong>
                    </p>
                </div>

                <button class="vuero-button is-primary" type="button">View Schedule</button>
            </section>

            <section class="interview-summary" aria-label="Interview statistics">
                <div class="interview-stats">
                    <x-vuero.dashboard.stat label="Minutes" value="62K" tone="info" icon="clock" />
                    <x-vuero.dashboard.stat label="Interviews" value="263" tone="purple" icon="radio" />
                    <x-vuero.dashboard.stat label="Approved" value="49" tone="green" icon="user-check" />
                    <x-vuero.dashboard.stat label="Rejected" value="214" tone="orange" icon="frown" />
                </div>

                <div class="interview-total">
                    <div>
                        <h2>Total Interviews</h2>
                        <p><strong>23 interviews</strong> this month</p>
                    </div>
                    <div class="avatar-row" aria-label="Interviewers">
                        <span><img src="{{ asset('vuero/images/admin/business/avatars/5.svg') }}" alt="Mary L."></span>
                        <span><img src="{{ asset('vuero/images/admin/business/avatars/18.svg') }}" alt="Esteban C."></span>
                        <span aria-label="17 more interviewers">+17</span>
                    </div>
                </div>
            </section>

            <figure class="interview-illustration">
                <img src="{{ asset('vuero/images/admin/dashboards/interviews.svg') }}" alt="People preparing for interviews">
            </figure>

            <x-vuero.dashboard.card title="Interviews" class="interview-chart-card">
                <x-vuero.dashboard.chart tone="purple" height="200" />
            </x-vuero.dashboard.card>
        </main>

        <aside class="interview-sidebar" aria-label="Interview calendar and appointments">
            <x-vuero.dashboard.card class="interview-calendar-card">
                <x-vuero.dashboard.calendar />
            </x-vuero.dashboard.card>

            @foreach ([
                ['avatar' => '32', 'name' => 'Jonathan K.', 'time' => '08:00 - 09:00'],
                ['avatar' => '19', 'name' => 'Greta K.', 'time' => '10:30 - 11:00'],
                ['avatar' => '12', 'name' => 'Joshua S.', 'time' => '02:30 - 03:00'],
            ] as $meeting)
                <button class="dashboard-card meeting-card" type="button">
                    <img src="{{ asset('vuero/images/admin/business/avatars/'.$meeting['avatar'].'.svg') }}" alt="">
                    <p>
                        <strong>{{ $meeting['name'] }}</strong>
                        <small>{{ $meeting['time'] }}</small>
                    </p>
                    <span aria-hidden="true"><x-vuero.icon name="chevron-left" size="18" /></span>
                </button>
            @endforeach
        </aside>
    </div>
</div>
