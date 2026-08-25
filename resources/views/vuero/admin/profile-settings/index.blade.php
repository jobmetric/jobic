<x-vuero.layouts.app title="Profile Settings" script="resources/js/vuero/pages/admin/profile-settings.js">
    @php
        $userName = auth()->user()->name ?? 'Erik Kovalsky';
        $initials = collect(preg_split('/\s+/', $userName))->filter()->take(2)->map(fn (string $part): string => strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'EK';
    @endphp

    <section class="profile-settings" data-profile-settings>
        <header class="profile-settings__header">
            <span class="vuero-avatar profile-settings__avatar"><span>{{ $initials }}</span><i class="country-flag country-flag--us"></i></span>
            <h2>{{ $userName }}</h2>
            <p>Hey everyone, I am a product manager from New York and I am looking for new opportunities in the software business.</p>
            <div class="profile-settings__stats">
                <span><x-vuero.icon name="users" size="18" />500+ Relations</span><i></i><span><x-vuero.icon name="check" size="18" />78 Projects</span><i></i>
                <span class="profile-settings__socials"><a href="#">f</a><a href="#">t</a><a href="#">in</a></span>
            </div>
        </header>

        <div class="settings-grid">
            @foreach ($settings as $setting)
                <button class="settings-box" type="button" data-setting-card="{{ str($setting['label'])->slug() }}">
                    <span class="settings-box__edit"><x-vuero.icon name="edit" size="11" /></span>
                    <span class="settings-box__icon"><x-vuero.icon :name="$setting['icon']" size="36" /></span>
                    <span>{{ $setting['label'] }}</span>
                    <strong>{{ $setting['title'] }}</strong>
                </button>
            @endforeach
        </div>
    </section>
</x-vuero.layouts.app>
