@if(request()->routeIs('admin.extension-examples.system') && app(\App\Support\AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.examples.system'))
<p class="crud-page crud-hint">{{ __('extension_component_jobic_system_example::example.hook_notice') }}</p>
@endif
