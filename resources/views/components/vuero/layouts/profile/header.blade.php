@props(['profile'])

<header class="profile-header">
    <x-vuero.layouts.flex-avatar size="large" :picture="$profile['avatar']" :badge="$profile['flag']" />
    <h2>{{ $profile['name'] }}</h2>
    <p>{{ $profile['bio'] }}</p>
    <div class="profile-stats">
        <div class="profile-stat"><x-vuero.icon name="users" size="18" /><span>{{ $profile['relations'] }}</span></div>
        <span class="separator"></span>
        <div class="profile-stat"><x-vuero.icon name="check" size="18" /><span>{{ $profile['projects'] }}</span></div>
        <span class="separator"></span>
        <div class="socials"><a href="#">f</a><a href="#">t</a><a href="#">in</a></div>
    </div>
</header>
