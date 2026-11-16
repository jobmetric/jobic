@if(request()->routeIs('admin.extension-examples.multiple') && app(\App\Support\AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.examples.multiple'))
<p class="crud-page crud-hint">{{ __('extension_module_jobic_multiple_example::example.hook_notice') }}</p>
@endif
