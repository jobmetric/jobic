<div class="cms-avatar-picker">
    <div class="cms-avatar-picker__columns">
        <section class="cms-gravatar-card">
            <span class="cms-avatar-picker__eyebrow">{{ __('admin.gravatar') }}</span>
            <div class="cms-gravatar-preview">
                <img src="{{ $account->gravatarUrl(true) }}" alt="" data-gravatar-preview>
                <span data-gravatar-fallback hidden><x-vuero.icon name="user" size="30" /></span>
            </div>
            <h3>{{ __('admin.gravatar_title') }}</h3>
            <p>{{ __('admin.gravatar_help') }}</p>
            <a href="https://gravatar.com/profile" target="_blank" rel="noopener noreferrer">{{ __('admin.gravatar_upload') }}</a>
            <button type="button" class="vuero-button is-primary @if((int) $account->avatar_preset === -1) is-selected @endif" data-avatar-gravatar data-avatar-choice="-1" data-avatar-url="{{ $account->gravatarUrl() }}">
                <x-vuero.icon name="check" size="15" />{{ __('admin.use_gravatar') }}
            </button>
        </section>
        <section class="cms-avatar-presets">
            <span class="cms-avatar-picker__eyebrow">{{ __('admin.preset_avatars') }}</span>
            <p>{{ __('admin.avatar_modal_hint') }}</p>
            <div class="cms-avatar-modal__grid">
                @foreach(range(1, 45) as $avatarNumber)
                    <button type="button" class="cms-avatar-choice @if((int) old('avatar_preset', $account->avatar_preset) === $avatarNumber) is-selected @endif" data-avatar-choice="{{ $avatarNumber }}" data-avatar-url="{{ asset(sprintf('vuero/images/admin/avatars/presets/avatar-%02d.webp', $avatarNumber)) }}" aria-label="{{ __('admin.avatar_choice', ['number' => $avatarNumber]) }}">
                        <img src="{{ asset(sprintf('vuero/images/admin/avatars/presets/avatar-%02d.webp', $avatarNumber)) }}" alt="" loading="lazy">
                        <span><x-vuero.icon name="check" size="13" /></span>
                    </button>
                @endforeach
            </div>
        </section>
    </div>
</div>
