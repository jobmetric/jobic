@if(request()->routeIs('admin.extension-examples.plugin') && app(\App\Support\AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.examples.plugin'))
<p class="crud-page crud-hint">{{ __('extension_plugin_jobic_plugin_example::example.hook_notice') }}</p>
@endif
