<dialog class="panel-file-picker-modal" data-media-manager data-file-picker-modal data-browse-url="{{ route('admin.media-manager.browse') }}" data-upload-url="{{ route('admin.media-manager.upload') }}" data-folder-url="{{ route('admin.media-manager.folders.store') }}" data-items-url="{{ url('/admin/media-manager') }}">
    <header class="panel-file-picker-modal__head"><div><span><x-vuero.icon name="folder" size="18" /></span><div><strong>{{ __('posts.choose_media') }}</strong></div></div><button type="button" data-file-picker-close aria-label="{{ __('posts.cancel') }}"><x-vuero.icon name="x" size="16" /></button></header>
    @include('media.workspace', ['mode' => 'picker'])
</dialog>
