<x-vuero.layouts.app title="Profile Notifications" :script="$layoutScript">
    <section class="profile-layout-page timeline-wrapper" data-profile-notifications aria-label="Profile Notifications">
        <div class="timeline-header" aria-hidden="true"></div>
        <div class="timeline-wrapper-inner">
            <div class="timeline-container">
                @foreach ($items as $item)
                    <article @class(['timeline-item', 'is-unread' => $item['unread'] ?? false])>
                        <div class="date"><span>{{ $item['date'] }}</span></div>
                        <div @class(['dot', 'is-'.$item['tone']])></div>
                        <div class="content-wrap">
                            <div class="content-box">
                                <div class="status" aria-hidden="true"></div>
                                <x-vuero.layouts.flex-avatar :picture="$item['avatar']" />
                                <div class="box-text">
                                    <div class="meta-text">
                                        <p>{!! $item['html'] !!}</p>
                                        <span>{{ $item['time'] }}</span>
                                    </div>
                                </div>
                                @if (! empty($item['end']))
                                    <div class="box-end">
                                        @foreach ($item['end'] as $avatar)
                                            <x-vuero.layouts.flex-avatar
                                                size="small"
                                                :picture="$avatar['picture'] ?? null"
                                                :initials="$avatar['initials'] ?? null"
                                                :color="$avatar['color'] ?? null"
                                                :squared="$avatar['squared'] ?? false"
                                            />
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="timeline-load-more load-more-wrap has-text-centered">
                <button type="button" class="v-button is-dark-outlined" data-timeline-load-more>Load More</button>
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
