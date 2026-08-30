<x-vuero.layouts.app title="Profile View" :script="$layoutScript">
    <section class="profile-layout-page profile-wrapper" data-profile-view aria-label="Profile View">
        <x-vuero.layouts.profile.header :profile="$profile" />

        <div class="profile-body">
            <div class="columns">
                <div class="column is-8">
                    <div class="profile-card">
                        <div class="profile-card-section">
                            <div class="section-title">
                                <h4>About Me</h4>
                                <a href="{{ route('vuero.admin.layouts.profile-edit', 'general') }}" aria-label="Edit about"><x-vuero.icon name="edit" size="14" /></a>
                            </div>
                            <div class="section-content">
                                <p class="description">{{ $about }}</p>
                            </div>
                        </div>

                        <div class="profile-card-section">
                            <div class="section-title">
                                <h4>Experience</h4>
                                <a href="{{ route('vuero.admin.layouts.profile-edit', 'experience') }}" aria-label="Edit experience"><x-vuero.icon name="edit" size="14" /></a>
                            </div>
                            <div class="section-content">
                                <div class="experience-wrapper">
                                    @foreach ($experiences as $index => $experience)
                                        <div class="experience-item" @if ($index >= 4) hidden data-profile-more-item @endif>
                                            <img src="{{ $experience['logo'] }}" alt="">
                                            <div class="meta">
                                                <span class="dark-inverted">{{ $experience['company'] }}</span>
                                                <span><span>{{ $experience['start'] }}</span><i aria-hidden="true"></i><span>{{ $experience['end'] }}</span></span>
                                                <span>{{ $experience['role'] }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if (count($experiences) > 4)
                                    <div class="more-button">
                                        <button type="button" class="v-button is-light" data-profile-view-more="experience">View More</button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="profile-card-section">
                            <div class="section-title">
                                <h4>Languages</h4>
                                <a href="{{ route('vuero.admin.layouts.profile-edit', 'skills') }}" aria-label="Edit languages"><x-vuero.icon name="edit" size="14" /></a>
                            </div>
                            <div class="section-content">
                                <div class="languages-wrapper">
                                    @foreach ($languages as [$name, $flag, $level, $progress])
                                        <div class="languages-item">
                                            <span class="profile-icon-wrap">
                                                <img src="{{ $flag }}" alt="">
                                                <span class="profile-donut" style="--progress: {{ $progress }}"></span>
                                            </span>
                                            <div class="meta">
                                                <span class="dark-inverted">{{ $name }}</span>
                                                <span>{{ $level }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="profile-card-section">
                            <div class="section-title">
                                <h4>Skills</h4>
                                <a href="{{ route('vuero.admin.layouts.profile-edit', 'skills') }}" aria-label="Edit skills"><x-vuero.icon name="edit" size="14" /></a>
                            </div>
                            <div class="section-content">
                                <div class="skills-wrapper">
                                    @foreach ($skills as $index => $skill)
                                        <div class="skills-item" @if ($index >= 4) hidden data-profile-more-item @endif>
                                            <span @class(['profile-icon-wrap', 'is-skill', 'is-placeholder' => ! $skill[1]])>
                                                @if ($skill[1])
                                                    <img src="{{ $skill[1] }}" alt="">
                                                @else
                                                    <x-vuero.icon name="briefcase" size="20" />
                                                @endif
                                            </span>
                                            <div class="skill-info">
                                                <span class="dark-inverted">{{ $skill[0] }}</span>
                                                <span>{{ $skill[2] }}</span>
                                            </div>
                                            <div class="people">
                                                @foreach ($skill[3] as $person)
                                                    <x-vuero.layouts.flex-avatar
                                                        size="small"
                                                        :picture="$person['picture'] ?? null"
                                                        :initials="$person['initials'] ?? null"
                                                        :color="$person['color'] ?? null"
                                                    />
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if (count($skills) > 4)
                                    <div class="more-button">
                                        <button type="button" class="v-button is-light" data-profile-view-more="skills">View More</button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="profile-card">
                        <div class="profile-card-section no-padding">
                            <div class="section-title">
                                <h4>Recommendations</h4>
                                <a href="#" aria-label="Edit recommendations"><x-vuero.icon name="edit" size="14" /></a>
                                <a class="action-link" href="#">View All</a>
                            </div>
                            <div class="section-content">
                                <div class="recommendations-wrapper">
                                    @foreach ($recommendations as $item)
                                        <div class="recommendations-item">
                                            <x-vuero.layouts.flex-avatar size="large" :picture="$item['avatar']" :badge="$item['badge']" />
                                            <h3 class="dark-inverted">{{ $item['role'] }}</h3>
                                            <p>{{ $item['text'] }}</p>
                                            <div class="meta">
                                                <span>{{ $item['name'] }}</span>
                                                <span>{{ $item['date'] }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="column is-4">
                    <div class="profile-card">
                        <div class="profile-card-section no-padding">
                            <div class="section-title">
                                <h4>Notifications</h4>
                                <div class="control">
                                    <label class="form-switch is-success">
                                        <input type="checkbox" checked data-profile-network-switch>
                                        <i aria-hidden="true"></i>
                                    </label>
                                </div>
                            </div>
                            <div class="section-content">
                                <div class="network-notifications">
                                    <h3 class="dark-inverted">Notify Your Network?</h3>
                                    <p>Enable or disable this setting to manage if your network should be notified when you make changes to your profile.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-card">
                        <div class="profile-card-section no-padding">
                            <div class="section-title">
                                <h4>Tools</h4>
                                <a href="{{ route('vuero.admin.layouts.profile-edit', 'skills') }}" aria-label="Edit tools"><x-vuero.icon name="edit" size="14" /></a>
                                <a class="action-link" href="#">View All</a>
                            </div>
                            <div class="section-content">
                                <div class="tools-wrapper">
                                    @foreach ($tools as [$name, $icon, $level, $progress])
                                        <div class="tools-item">
                                            <span class="profile-icon-wrap">
                                                <img src="{{ $icon }}" alt="">
                                                <span class="profile-pie" style="--progress: {{ $progress }}"></span>
                                            </span>
                                            <div class="meta">
                                                <span class="dark-inverted">{{ $name }}</span>
                                                <span>{{ $level }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-card">
                        <div class="profile-card-section no-padding">
                            <div class="section-title">
                                <h4>Recent Views</h4>
                                <a class="action-link" href="#">View All</a>
                            </div>
                            <div class="section-content">
                                <div class="people-wrapper">
                                    @foreach ($recentViews as [$name, $role, $avatar, $badge])
                                        <a class="people-item" href="#">
                                            <x-vuero.layouts.flex-avatar :picture="$avatar" :badge="$badge" />
                                            <div class="meta">
                                                <span class="dark-inverted">{{ $name }}</span>
                                                <span>{{ $role }}</span>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
