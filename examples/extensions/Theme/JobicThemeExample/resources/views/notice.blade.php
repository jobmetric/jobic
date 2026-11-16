@if(request()->routeIs('admin.extension-examples.theme') && app(\App\Support\AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.examples.theme'))
<p class="crud-page crud-hint">{{ __('extension_theme_jobic_theme_example::example.hook_notice') }}</p>
@endif
