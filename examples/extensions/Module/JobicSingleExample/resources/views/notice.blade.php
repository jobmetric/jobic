@if(request()->routeIs('admin.extension-examples.single') && app(\App\Support\AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.examples.single'))
<p class="crud-page crud-hint">{{ __('extension_module_jobic_single_example::example.hook_notice') }}</p>
@endif
