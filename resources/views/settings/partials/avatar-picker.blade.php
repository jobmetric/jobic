<div class="cms-avatar-picker">
    <p>{{ __('admin.avatar_modal_hint') }}</p>
    <div class="cms-avatar-modal__grid">
        @foreach(range(1, 45) as $avatarNumber)
            <button type="button" class="cms-avatar-choice @if((int) old('avatar_preset', $account->avatar_preset) === $avatarNumber) is-selected @endif" data-avatar-choice="{{ $avatarNumber }}" data-avatar-url="{{ asset(sprintf('vuero/images/admin/avatars/presets/avatar-%02d.webp', $avatarNumber)) }}" aria-label="{{ __('admin.avatar_choice', ['number' => $avatarNumber]) }}">
                <img src="{{ asset(sprintf('vuero/images/admin/avatars/presets/avatar-%02d.webp', $avatarNumber)) }}" alt="" loading="lazy">
                <span><x-vuero.icon name="check" size="13" /></span>
            </button>
        @endforeach
    </div>
</div>
