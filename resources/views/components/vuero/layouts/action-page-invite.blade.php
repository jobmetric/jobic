@php
    $avatar = '/vuero/images/layouts/list-view-v1/avatars/16.jpg';
    $badge = '/vuero/images/layouts/action-pages/apps/9.png';
    $team = [
        ['picture' => '/vuero/images/layouts/list-view-v1/avatars/21.jpg'],
        ['initials' => 'SC', 'color' => 'h-purple'],
        ['picture' => '/vuero/images/layouts/list-view-v1/avatars/39.jpg'],
    ];
@endphp

<div class="box-content">
    <x-vuero.layouts.flex-avatar size="big" :picture="$avatar" :badge="$badge" />

    <h3 class="dark-inverted">
        Jason G. has invited you to the
        <span class="is-dark-primary">Banking Solution Website</span>
        project.
    </h3>

    <div class="sender-message is-dark-card-bordered is-dark-bg-4">
        <h4 class="dark-inverted">Message from Jason</h4>
        <p>
            Hey Erik, It would be really cool if you could give us a hand on this
            project. There are a lot of tasks popping out every day and I feel the team
            is getting a bit overwhelmed. We'd love to have you board.
        </p>
    </div>

    <div class="people-wrap">
        <div class="people">
            @foreach ($team as $member)
                <div>
                    <x-vuero.layouts.flex-avatar
                        size="small"
                        :picture="$member['picture'] ?? null"
                        :initials="$member['initials'] ?? null"
                        :color="$member['color'] ?? null"
                    />
                </div>
            @endforeach
        </div>
        <div class="people-text">
            <p>And 5 others are already members of this project</p>
        </div>
    </div>

    <div class="buttons">
        <button type="button" class="v-button is-dark-outlined" data-action-decline>Decline</button>
        <button type="button" class="v-button is-primary is-raised" data-action-accept>Accept</button>
    </div>
</div>
