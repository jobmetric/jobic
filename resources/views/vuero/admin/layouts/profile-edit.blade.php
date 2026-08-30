<x-vuero.layouts.app title="Profile Edit" :script="$layoutScript">
    <section class="profile-layout-page account-wrapper" data-profile-edit aria-label="Profile Edit">
        <div class="columns">
            <div class="column is-4">
                <div class="account-box is-navigation">
                    <div class="media-flex-center">
                        <x-vuero.layouts.flex-avatar size="large" :picture="$profile['avatar']" :badge="$profile['flag']" />
                        <div class="meta">
                            <span>{{ $profile['name'] }}</span>
                            <span>{{ $profile['role'] }}</span>
                        </div>
                    </div>
                    <x-vuero.layouts.profile.edit-nav :profile="$profile" :tab="$tab" />
                </div>
            </div>

            <div class="column is-8">
                @include("vuero.admin.layouts.profile-edit.{$tab}")
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
