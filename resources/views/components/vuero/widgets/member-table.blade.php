@props(['members', 'squared' => false])

<article class="widget-card widget-member-card" data-widget-card>
    <header class="widget-card__header">
        <h2>Members</h2>
        <button class="widget-primary-button" type="button" data-widget-add-member><x-vuero.icon name="plus" size="15" />Add Member</button>
    </header>
    <div class="widget-member-table-wrap">
        <table class="widget-member-table">
            <thead><tr><th>Member</th><th>Membership</th><th>Income</th><th>Skills</th><th>Rating</th><th>Status</th><th></th></tr></thead>
            <tbody data-widget-members>
                @foreach ($members as $member)
                    <tr>
                        <td><div class="widget-member"><x-vuero.widgets.avatar :src="$member['picture']" :name="$member['name']" :squared="$squared" /><span><strong>{{ $member['name'] }}</strong><small>{{ strtolower(str_replace(' ', '.', $member['name'])) }}@vuero.io</small></span></div></td>
                        <td>{{ $member['membership'] }}</td><td>${{ $member['income'] }}</td>
                        <td><span class="widget-skill-stack"><i>JS</i><i>UI</i><i>+{{ $loop->iteration }}</i></span></td>
                        <td><span class="widget-rating">★★★★★</span></td>
                        <td><span class="widget-status is-{{ strtolower($member['status']) }}">{{ $member['status'] }}</span></td>
                        <td><button type="button" class="widget-row-menu" aria-label="Member actions"><x-vuero.icon name="more" size="16" /></button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</article>
