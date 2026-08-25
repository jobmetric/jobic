<x-vuero.layouts.app title="Analytics Dashboard" script="resources/js/vuero/pages/admin/dashboard.js">
    <section class="analytics-dashboard" aria-label="Analytics Dashboard" data-dashboard>
        <div class="analytics-dashboard__main">
            <div class="analytics-metrics">
                @foreach ($metrics as $metric)
                    <article class="analytics-tile">
                        <header class="analytics-tile__head">
                            <h2>{{ $metric['label'] }}</h2>
                            <span class="analytics-icon analytics-icon--{{ $metric['tone'] }}">
                                <x-vuero.icon :name="$metric['icon']" size="19" />
                            </span>
                        </header>
                        <strong class="analytics-tile__value">{{ $metric['value'] }}</strong>
                        <footer class="analytics-tile__foot">
                            <span class="trend trend--{{ $metric['trend'] }}">
                                {{ $metric['change'] }}
                                <x-vuero.icon :name="'trending-'.$metric['trend']" size="16" />
                            </span>
                            <span>{{ $metric['caption'] }}</span>
                        </footer>
                    </article>
                @endforeach
            </div>

            <div class="analytics-grid analytics-grid--primary">
                <article class="vuero-card revenue-card">
                    <header class="vuero-card__head"><h2>Revenue</h2></header>
                    <div class="revenue-stats">
                        <div><span>This Month</span><strong class="is-primary">$75,648.43</strong></div>
                        <div><span>Last Month</span><strong>$91,512.18</strong></div>
                    </div>
                    <div class="revenue-chart" role="img" aria-label="Revenue from February to October">
                        <svg viewBox="0 0 520 235" preserveAspectRatio="none">
                            <g class="revenue-chart__grid">
                                <path d="M54 16H510M54 57H510M54 98H510M54 139H510M54 180H510M54 221H510" />
                                <path d="M54 221V227M111 221V227M168 221V227M225 221V227M282 221V227M339 221V227M396 221V227M453 221V227M510 221V227" />
                            </g>
                            <g class="revenue-chart__axis">
                                <text x="5" y="20">100000</text><text x="12" y="61">80000</text><text x="12" y="102">60000</text>
                                <text x="12" y="143">40000</text><text x="12" y="184">20000</text><text x="42" y="224">0</text>
                            </g>
                            <path class="revenue-chart__line" d="M54 200 C75 195 78 143 111 139 S156 158 181 149 207 117 235 122 281 141 302 120 331 92 359 89 396 84 418 82 440 18 465 35 490 60 510 64" />
                        </svg>
                        <div class="revenue-chart__labels"><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span></div>
                    </div>
                </article>

                <article class="vuero-card goal-card">
                    <header class="vuero-card__head"><h2>Goal Overview</h2></header>
                    <div class="goal-gauge" aria-label="Total goal progress 71.5 percent">
                        <svg viewBox="0 0 180 180" aria-hidden="true">
                            <circle class="goal-gauge__track" cx="90" cy="90" r="67" />
                            <circle class="goal-gauge__track goal-gauge__track--inner" cx="90" cy="90" r="59" />
                            <circle class="goal-gauge__value" cx="90" cy="90" r="67" />
                            <circle class="goal-gauge__value goal-gauge__value--inner" cx="90" cy="90" r="59" />
                        </svg>
                        <div><span>Total</span><strong>71.5%</strong></div>
                    </div>
                    <div class="goal-stats">
                        <div><span>Completed</span><strong>1,223</strong></div>
                        <div><span>In Progress</span><strong>467</strong></div>
                    </div>
                </article>
            </div>

            <div class="analytics-grid analytics-grid--secondary">
                <article class="vuero-card sales-card">
                    <header class="vuero-card__head"><h2>Sales</h2></header>
                    <strong class="sales-card__value">78%</strong>
                    <div class="vuero-progress"><span style="width: 78%"></span></div>
                    <div class="analytics-tile__foot"><span class="trend trend--up">+6.4% <x-vuero.icon name="trending-up" size="16" /></span><span>since last month</span></div>
                    <div class="sales-radial"><span>Growth</span><strong>52%</strong></div>
                </article>

                <article class="vuero-card profit-card">
                    <header class="vuero-card__head"><h2>Profit</h2></header>
                    <div class="profit-legend"><span><i class="is-blue"></i>Net Profit</span><span><i class="is-purple"></i>Revenue</span><span><i class="is-green"></i>Free Cash Flow</span></div>
                    <div class="profit-chart" aria-label="Profit bar chart">
                        @foreach ([45, 72, 58, 86, 64, 96, 71, 110, 82, 118, 92, 128] as $height)
                            <span style="--bar-height: {{ $height }}px"></span>
                        @endforeach
                    </div>
                </article>
            </div>
        </div>

        <aside class="analytics-dashboard__rail">
            <article class="vuero-widget contact-widget">
                <div class="contact-widget__identity">
                    <div><h2>{{ auth()->user()->name ?? 'Erik K.' }}</h2><a href="#">Vuero Ltd.</a><span>Product Manager</span></div>
                    <span class="vuero-avatar vuero-avatar--large"><span>EK</span></span>
                </div>
                <p><x-vuero.icon name="location" size="15" /> Los Angeles, CA</p>
                <p><x-vuero.icon name="phone" size="15" /> +1 444-5156</p>
                <a class="contact-widget__email" href="mailto:{{ auth()->user()->email ?? 'erikkovalsky@vuero.io' }}">{{ auth()->user()->email ?? 'erikkovalsky@vuero.io' }}</a>
            </article>

            <article class="vuero-widget calendar-widget">
                <header><button type="button" aria-label="Previous month"><x-vuero.icon name="chevron-left" size="17" /></button><h2>October 2020</h2><button type="button" aria-label="Next month"><x-vuero.icon name="chevron-right" size="17" /></button></header>
                <table>
                    <thead><tr><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th></tr></thead>
                    <tbody>
                        <tr><td class="is-muted">29</td><td class="is-muted">30</td><td class="is-muted">31</td><td>1</td><td>2</td><td>3</td><td>4</td></tr>
                        <tr><td>5</td><td>6</td><td>7</td><td>8</td><td>9</td><td>10</td><td>11</td></tr>
                        <tr><td>12</td><td>13</td><td>14</td><td>15</td><td>16</td><td>17</td><td class="is-current">18</td></tr>
                        <tr><td>19</td><td>20</td><td>21</td><td>22</td><td>23</td><td>24</td><td>25</td></tr>
                        <tr><td>26</td><td>27</td><td>28</td><td>29</td><td>30</td><td>31</td><td class="is-muted">1</td></tr>
                    </tbody>
                </table>
            </article>

            <article class="vuero-widget followers-widget">
                <header><h2>New Followers</h2><div class="avatar-stack"><span>A</span><span>E</span><span>M</span></div></header>
                <p>Great News! <strong>Alice</strong>, <strong>Erik</strong> and <strong>Mary</strong> are now following you. Take some time to look at their profile.</p>
            </article>

            <article class="vuero-widget rail-notifications">
                <header><h2>Notifications</h2><button type="button" aria-label="Notification options">⋮</button></header>
                <div><span>Personal</span><strong>4</strong></div>
                <div><span>Business</span><strong>2</strong></div>
            </article>
        </aside>
    </section>
</x-vuero.layouts.app>
