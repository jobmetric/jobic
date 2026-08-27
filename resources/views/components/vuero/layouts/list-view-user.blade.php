@props(['user', 'index'])

@php
    $labels = ['Projects', 'Replies', 'Posts'];
    $menuId = 'list-user-menu-'.$index;
@endphp

<article class="list-view-user" data-list-item data-search="{{ Illuminate\Support\Str::lower($user['name'].' '.$user['location']) }}" style="--item-order: {{ $index }}">
    <div class="list-user-avatar">
        <img src="{{ $user['avatar'] }}" alt="{{ $user['name'] }}">
        <img class="list-user-flag" src="{{ $user['flag'] }}" alt="">
    </div>

    <div class="list-user-identity">
        <h2>{{ $user['name'] }}</h2>
        <p><x-vuero.icon name="location" size="12" /><span>{{ $user['location'] }}</span></p>
    </div>

    <div class="list-user-meta">
        <span class="list-user-role is-{{ $user['tone'] }}">{{ $user['role'] }}</span>

        <dl class="list-user-stats">
            @foreach ($user['stats'] as $statIndex => $stat)
                <div><dt>{{ $labels[$statIndex] }}</dt><dd>{{ $stat }}</dd></div>
            @endforeach
        </dl>

        <div class="list-user-network">
            <div class="list-user-team" aria-label="{{ count($user['team']) }} team members">
                @foreach (array_slice($user['team'], 0, 3) as $member)
                    <img src="{{ str_ends_with($member, '.svg') ? '/vuero/images/layouts/list-view-v1/avatars/'.$member : '/vuero/images/layouts/list-view-v1/avatars/'.$member }}" alt="">
                @endforeach
                @if (count($user['team']) > 3)<span>+{{ count($user['team']) - 3 }}</span>@endif
            </div>
            <span>in Team</span>
        </div>

        <div class="list-user-menu" data-dropdown>
            <button type="button" aria-label="Actions for {{ $user['name'] }}" aria-haspopup="menu" aria-controls="{{ $menuId }}" aria-expanded="false" data-dropdown-toggle><x-vuero.icon name="more" size="19" /></button>
            <div class="list-user-menu__dropdown" id="{{ $menuId }}" role="menu" data-dropdown-menu hidden>
                @foreach ([
                    ['user', 'Profile', 'View profile'],
                    ['message', 'Message', 'Send Message'],
                    ['briefcase', 'Transfer', 'Transfer to other list'],
                ] as [$icon, $label, $description])
                    <button type="button" role="menuitem" data-list-action="{{ strtolower($label) }}" data-user-name="{{ $user['name'] }}">
                        <span><x-vuero.icon :name="$icon" size="18" /></span><span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
                    </button>
                @endforeach
                <hr>
                <button type="button" role="menuitem" class="is-danger" data-list-action="remove" data-user-name="{{ $user['name'] }}">
                    <span><x-vuero.icon name="trash" size="18" /></span><span><strong>Remove</strong><small>Remove from list</small></span>
                </button>
            </div>
        </div>
    </div>
</article>
