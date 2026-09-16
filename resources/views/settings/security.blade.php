@extends('common.settings.page')
@section('settings-content')
    <form method="POST" action="{{ route('admin.settings.security.update') }}" class="cms-settings-form">@csrf @method('PUT')
        <header class="cms-security-heading"><span class="cms-security-icon"><x-vuero.icon name="lock" size="24" /></span><div><h3>{{ __('admin.password_title') }}</h3><p>{{ __('admin.password_intro') }}</p></div></header>
        <div class="cms-password-grid">
            @foreach(['password' => 'new-password', 'password_confirmation' => 'confirm-password'] as $field => $id)
                <div><label class="installation-field" for="{{ $id }}"><span>{{ __('admin.fields.'.$field) }}</span></label><div class="cms-password-input"><input id="{{ $id }}" name="{{ $field }}" type="password" minlength="12" required autocomplete="new-password" @if($field === 'password') aria-describedby="password-guidance" @endif /><button type="button" data-password-toggle="{{ $id }}" aria-label="{{ __('admin.show_password') }}" aria-pressed="false"><x-vuero.icon name="eye" size="20" /></button></div>
                @if($field === 'password')<meter class="cms-password-strength" min="0" max="4" value="0" low="2" high="3" optimum="4" aria-label="{{ __('admin.password_strength') }}"></meter>@endif</div>
            @endforeach
        </div>
        <p id="password-guidance" class="cms-password-guidance">{{ __('admin.password_guidance') }}</p>
        <footer class="cms-security-footer"><button class="vuero-button is-primary" type="submit">{{ __('admin.save_password') }}</button></footer>
    @cmsHook('jobic.admin.settings.form.end', ['section' => 'security'])
    </form>
    <div class="cms-settings-form"><h3>{{ __('admin.recent_activity') }}</h3>
        @forelse($loginEvents as $event)<article class="cms-login-event"><span>{{ $event->created_at->format('Y-m-d H:i') }}</span><span dir="ltr">{{ $event->ip_address }}</span><span>{{ __('admin.'.($event->successful ? 'successful' : 'failed')) }}</span></article>@empty<p>{{ __('admin.no_activity') }}</p>@endforelse
    </div>
@endsection
