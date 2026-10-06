@php($picker = ($mode ?? 'standalone') === 'picker')
<section class="panel-file-manager {{ $picker ? 'panel-file-manager--picker' : '' }}"
         data-file-manager
         data-mode="{{ $picker ? 'picker' : 'standalone' }}"
         data-root-id="0"
         tabindex="-1">
    <div class="panel-file-manager__workspace">
        <main class="panel-file-manager__browser">
            <header class="panel-file-manager__toolbar">
                <div class="panel-file-manager__toolbar-actions" aria-label="{{ __('file-manager.texts.385') }}">
                    <button type="button" class="panel-file-manager__tool" data-fm-back title="{{ __('file-manager.texts.292') }}" aria-label="{{ __('file-manager.texts.292') }}"><i class="ki-filled ki-arrow-right"></i></button>
                    <button type="button" class="panel-file-manager__tool" data-fm-refresh title="{{ __('file-manager.texts.293') }}" aria-label="{{ __('file-manager.texts.293') }}"><i class="ki-filled ki-arrows-circle"></i></button>
                    <button type="button" class="panel-file-manager__tool is-success" data-fm-new-folder title="{{ __('file-manager.texts.386') }}" aria-label="{{ __('file-manager.texts.386') }}"><i class="ki-filled ki-add-folder"></i></button>
                    <button type="button" class="panel-file-manager__tool is-info" data-fm-upload title="{{ __('file-manager.texts.295') }}" aria-label="{{ __('file-manager.texts.295') }}"><i class="ki-filled ki-cloud-add"></i></button>
                    <input type="file" class="hidden" data-fm-file-input multiple>
                </div>

                <div class="panel-file-manager__toolbar-search">
                    <label class="panel-file-manager__search"><input type="search" placeholder="{{ __('file-manager.texts.387') }}" data-fm-search autocomplete="off"><i class="ki-filled ki-magnifier"></i></label>
                    <details class="panel-file-manager__menu panel-file-manager__filters" data-fm-filters-menu>
                        <summary class="panel-file-manager__menu-trigger"><i class="ki-filled ki-filter"></i><span>{{ __('file-manager.texts.388') }}</span><i class="ki-filled ki-down"></i></summary>
                        <div class="panel-file-manager__filters-popover">
                            <header class="panel-file-manager__filter-head">
                                <span class="panel-file-manager__filter-head-icon"><i class="ki-filled ki-filter"></i></span>
                                <div><strong>{{ __('file-manager.texts.389') }}</strong><p>{{ __('file-manager.texts.390') }}</p></div>
                                <button type="button" data-fm-filter-reset><i class="ki-filled ki-arrows-circle"></i><span>{{ __('file-manager.texts.391') }}</span></button>
                            </header>
                            <div class="panel-file-manager__filter-body">
                                <div class="panel-file-manager__filter-fields">
                                    <label class="panel-file-manager__filter-field is-wide"><span><i class="ki-filled ki-document"></i> {{ __('file-manager.texts.392') }}</span><select data-fm-group><option value="">{{ __('file-manager.texts.393') }}</option><option value="image">{{ __('file-manager.texts.394') }}</option><option value="document">{{ __('file-manager.texts.395') }}</option><option value="audio">{{ __('file-manager.texts.396') }}</option><option value="video">{{ __('file-manager.texts.397') }}</option><option value="archive">{{ __('file-manager.texts.398') }} ZIP</option></select></label>
                                    <label class="panel-file-manager__filter-field is-wide"><span><i class="ki-filled ki-folder"></i> {{ __('file-manager.texts.399') }}</span><select data-fm-search-scope><option value="folder">{{ __('file-manager.texts.400') }}</option><option value="global">{{ __('file-manager.texts.401') }}</option></select></label>
                                    <div class="panel-file-manager__filter-sort-row">
                                        <label class="panel-file-manager__filter-field"><span><i class="ki-filled ki-sort"></i> {{ __('file-manager.texts.402') }}</span><select data-fm-direction><option value="asc">{{ __('file-manager.texts.3') }}</option><option value="desc">{{ __('file-manager.texts.4') }}</option></select></label>
                                        <label class="panel-file-manager__filter-field"><span><i class="ki-filled ki-arrow-right-left"></i> {{ __('file-manager.texts.403') }}</span><select data-fm-sort><option value="name">{{ __('file-manager.texts.404') }}</option><option value="updated_at">{{ __('file-manager.texts.405') }}</option><option value="created_at">{{ __('file-manager.texts.406') }}</option><option value="type">{{ __('file-manager.texts.392') }}</option></select></label>
                                    </div>
                                </div>
                                <aside class="panel-file-manager__filter-hint"><i class="ki-filled ki-information-2"></i><p>{{ __('file-manager.texts.407') }}</p></aside>
                            </div>
                        </div>
                    </details>
                    <details class="panel-file-manager__menu panel-file-manager__actions-menu is-disabled" data-fm-actions-menu>
                        <summary class="panel-file-manager__menu-trigger" aria-disabled="true" tabindex="-1"><i class="ki-filled ki-setting-2"></i><span>{{ __('file-manager.texts.408') }}</span><b class="panel-file-manager__selection-count hidden" data-fm-selection-count>{{ __('file-manager.texts.191') }}</b><i class="ki-filled ki-down"></i></summary>
                        <div class="panel-file-manager__actions-popover">
                            <header class="panel-file-manager__actions-head">
                                <span class="panel-file-manager__actions-head-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.25"/><circle cx="9" cy="10.5" r=".8"/><circle cx="15" cy="10.5" r=".8"/><path d="M8.5 14c.9 1.15 2.05 1.7 3.5 1.7s2.6-.55 3.5-1.7"/></svg>
                                </span>
                                <div><strong>{{ __('file-manager.texts.409') }}</strong><p data-fm-actions-summary>{{ __('file-manager.texts.24') }}</p></div>
                            </header>
                            <div class="panel-file-manager__actions-list">
                                <button type="button" class="is-copy" data-fm-copy>
                                    <span class="panel-file-manager__action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="8" y="4" width="11" height="13" rx="2.5"/><path d="M16 17v1a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1"/></svg></span>
                                    <span><strong>{{ __('file-manager.texts.302') }}</strong><small data-fm-action-description="copy">{{ __('file-manager.texts.410') }}</small></span><kbd>Ctrl+C</kbd>
                                </button>
                                <button type="button" class="is-cut" data-fm-cut>
                                    <span class="panel-file-manager__action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="6.5" cy="17.5" r="2.5"/><path d="m8.4 8.1 9.8 8.8M8.4 15.9 18.2 7M14.1 13l4.1 3.9"/></svg></span>
                                    <span><strong>{{ __('file-manager.texts.411') }}</strong><small data-fm-action-description="cut">{{ __('file-manager.texts.412') }}</small></span><kbd>Ctrl+X</kbd>
                                </button>
                                <button type="button" class="is-paste" data-fm-paste>
                                    <span class="panel-file-manager__action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 5.5H7.5A2.5 2.5 0 0 0 5 8v10.5A2.5 2.5 0 0 0 7.5 21h9a2.5 2.5 0 0 0 2.5-2.5V8a2.5 2.5 0 0 0-2.5-2.5H15"/><rect x="9" y="3" width="6" height="5" rx="1.5"/><path d="M12 11v6m-3-3h6"/></svg></span>
                                    <span><strong>{{ __('file-manager.texts.296') }}</strong><small>{{ __('file-manager.texts.413') }}</small></span><kbd>Ctrl+V</kbd>
                                </button>
                                <button type="button" class="is-archive" data-fm-compress>
                                    <span class="panel-file-manager__action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3.5h8l4 4v13H6z"/><path d="M14 3.5v4h4M10 7h3m-3 3h3m-3 3h3m-3 3h3"/><path d="M9 20h6"/></svg></span>
                                    <span><strong>{{ __('file-manager.texts.414') }} ZIP</strong><small data-fm-action-description="compress">{{ __('file-manager.texts.280') }} ZIP {{ __('file-manager.texts.415') }}</small></span><kbd>ZIP</kbd>
                                </button>
                                <button type="button" class="is-rename" data-fm-rename>
                                    <span class="panel-file-manager__action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m14.2 5.1 4.7 4.7M5 19l1.15-4.55L15.6 5a1.7 1.7 0 0 1 2.4 0l1 1a1.7 1.7 0 0 1 0 2.4l-9.45 9.45L5 19Z"/><path d="M13.6 7 17 10.4"/></svg></span>
                                    <span><strong>{{ __('file-manager.texts.276') }}</strong><small>{{ __('file-manager.texts.416') }}</small></span><kbd>F2</kbd>
                                </button>
                                <button type="button" class="is-danger" data-fm-delete>
                                    <span class="panel-file-manager__action-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7h16M9 4h6l1 3M7 7l.75 13h8.5L17 7M10 11v5m4-5v5"/></svg></span>
                                    <span><strong>{{ __('file-manager.texts.307') }}</strong><small data-fm-action-description="delete">{{ __('file-manager.texts.417') }}</small></span><kbd>Del</kbd>
                                </button>
                            </div>
                        </div>
                    </details>
                    <button type="button" class="panel-file-manager__details-toggle" data-fm-details-toggle aria-pressed="true" aria-label="{{ __('file-manager.texts.0') }}" title="{{ __('file-manager.texts.0') }}"><i class="ki-filled ki-information-2"></i></button>
                </div>
            </header>

            <div class="panel-file-manager__location-bar">
                <div class="panel-file-manager__location-view" data-fm-location-view role="group" aria-label="{{ __('file-manager.texts.418') }}">
                    <button type="button" class="is-active" data-fm-view="grid" title="{{ __('file-manager.texts.419') }}" aria-label="{{ __('file-manager.texts.419') }}" aria-pressed="true">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="3" width="5.5" height="5.5" rx="1.2"/><rect x="11.5" y="3" width="5.5" height="5.5" rx="1.2"/><rect x="3" y="11.5" width="5.5" height="5.5" rx="1.2"/><rect x="11.5" y="11.5" width="5.5" height="5.5" rx="1.2"/></svg>
                    </button>
                    <button type="button" data-fm-view="list" title="{{ __('file-manager.texts.420') }}" aria-label="{{ __('file-manager.texts.420') }}" aria-pressed="false">
                        <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="4" cy="5" r="1.25"/><circle cx="4" cy="10" r="1.25"/><circle cx="4" cy="15" r="1.25"/><path d="M8 5h8M8 10h8M8 15h8"/></svg>
                    </button>
                </div>
                <nav class="panel-file-manager__breadcrumbs" data-fm-breadcrumbs aria-label="{{ __('file-manager.texts.421') }}"></nav>
            </div>

            <div class="panel-file-manager__drop" data-fm-drop>
                <div class="panel-file-manager__list-head" aria-hidden="true"><span></span><span>{{ __('file-manager.texts.20') }}</span><span>{{ __('file-manager.texts.404') }}</span><span>{{ __('file-manager.texts.51') }}</span><span>{{ __('file-manager.texts.422') }}</span><span>{{ __('file-manager.texts.55') }}</span></div>
                <div class="panel-file-manager__items" data-fm-items role="listbox" aria-label="{{ __('file-manager.texts.423') }}"></div>
                <div class="panel-file-manager__load-more hidden" data-fm-load-more aria-live="polite">
                    <span></span><p>{{ __('file-manager.texts.382') }}</p>
                </div>
                <div class="panel-file-manager__empty hidden" data-fm-empty><i class="panel-file-manager__folder-illustration" aria-hidden="true"></i><strong>{{ __('file-manager.texts.424') }}</strong><p>{{ __('file-manager.texts.425') }}</p></div>
                <div class="panel-file-manager__loading" data-fm-loading><div>{{ __('file-manager.texts.426') }}</div></div>
                <div class="panel-file-manager__drag-overlay" data-fm-drag-overlay><div><i class="ki-filled ki-cloud-add"></i><strong>{{ __('file-manager.texts.427') }}</strong></div></div>
            </div>

            <section class="panel-toast-stack panel-file-manager__toast-stack"
                     data-fm-toast-stack
                     aria-label="{{ __('file-manager.texts.428') }}"></section>
        </main>

        <div class="panel-file-manager__details-resizer" data-fm-details-resizer role="separator" aria-label="{{ __('file-manager.texts.429') }}" aria-orientation="vertical" aria-valuemin="190" aria-valuemax="480" aria-valuenow="270" title="{{ __('file-manager.texts.430') }}" tabindex="0"></div>
        <aside class="panel-file-manager__sidebar" data-fm-details>
            <header class="panel-file-manager__sidebar-head"><div><i class="ki-filled ki-information-2"></i><strong>{{ __('file-manager.texts.48') }}</strong></div><button type="button" data-fm-details-close aria-label="{{ __('file-manager.texts.49') }}" title="{{ __('file-manager.texts.50') }}"><i class="ki-filled ki-cross"></i></button></header>
            <div class="panel-file-manager__details-empty"><i class="ki-filled ki-mouse-square"></i><strong>{{ __('file-manager.texts.46') }}</strong><p>{{ __('file-manager.texts.47') }}</p></div>
        </aside>
    </div>

    <footer class="panel-file-manager__footer">
        <div class="panel-file-manager__footer-start">
            <div class="panel-file-manager__upload-center">
                <section class="panel-file-manager__uploads hidden" data-fm-uploads>
                    <header>
                        <div class="panel-file-manager__uploads-title">
                            <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3.5h7l4 4v13H7z"/><path d="M14 3.5v4h4M10 13h5m-2.5-2.5L15 13l-2.5 2.5"/></svg></span>
                            <div><strong>{{ __('file-manager.texts.431') }}</strong><small data-fm-upload-summary>{{ __('file-manager.texts.333') }}</small></div>
                        </div>
                        <div class="panel-file-manager__uploads-head-actions">
                            <button type="button" data-fm-uploads-minimize aria-label="{{ __('file-manager.texts.432') }}" title="{{ __('file-manager.texts.432') }}"><i class="ki-filled ki-minus"></i></button>
                            <button type="button" data-fm-uploads-close aria-label="{{ __('file-manager.texts.50') }}" title="{{ __('file-manager.texts.50') }}"><i class="ki-filled ki-cross"></i></button>
                        </div>
                    </header>
                    <div data-fm-upload-list>
                        <div class="panel-file-manager__uploads-empty" data-fm-uploads-empty>
                            <span aria-hidden="true"><svg viewBox="0 0 64 64"><path d="M18 46h29a11 11 0 0 0 1.5-21.9A17 17 0 0 0 16 20.8 12.8 12.8 0 0 0 18 46Z"/><path d="M32 39V24m0 0-6 6m6-6 6 6"/></svg></span>
                            <strong>{{ __('file-manager.texts.433') }}</strong>
                            <p>{{ __('file-manager.texts.434') }}</p>
                            <button type="button" data-fm-empty-upload-trigger><i class="ki-filled ki-cloud-add"></i> {{ __('file-manager.texts.435') }}</button>
                            <small>{{ __('file-manager.texts.436') }} ZIP</small>
                        </div>
                    </div>
                </section>
                <button type="button" class="panel-file-manager__upload-toggle" data-fm-uploads-toggle title="{{ __('file-manager.texts.437') }}"><i class="ki-filled ki-cloud-add"></i><span data-fm-upload-count>0</span></button>
            </div>
            <label class="panel-file-manager__select-all"><input type="checkbox" data-fm-select-all><span>{{ __('file-manager.texts.291') }}</span></label>
            <span class="panel-file-manager__footer-count"><i class="ki-filled ki-files"></i><b data-fm-footer-count>{{ __('file-manager.texts.438') }}</b></span>
            <strong class="panel-file-manager__selected-summary hidden" data-fm-selected-summary></strong>
        </div>

        <div class="panel-file-manager__footer-end">
            @if($picker)
                <span class="panel-file-manager__picker-summary"><i class="ki-filled ki-information-2"></i><span data-fm-picker-summary>{{ __('file-manager.texts.37') }}</span></span>
                <button type="button" class="panel-file-manager__picker-confirm" data-fm-picker-confirm disabled><i class="ki-filled ki-check"></i><span>{{ __('file-manager.texts.435') }}</span></button>
            @else
                <span class="panel-file-manager__footer-hint"><i class="ki-filled ki-mouse-square"></i>{{ __('file-manager.texts.439') }}</span>
            @endif
        </div>
    </footer>

    <div class="panel-file-manager__context hidden" data-fm-context-menu role="menu"></div>

    <div class="panel-file-manager__prompt hidden" data-fm-prompt role="dialog" aria-modal="true">
        <form class="panel-file-manager__prompt-card" data-fm-prompt-form>
            <span class="panel-file-manager__prompt-icon"><i class="ki-filled ki-add-folder"></i></span>
            <div><strong data-fm-prompt-title>{{ __('file-manager.texts.272') }}</strong><p data-fm-prompt-text>{{ __('file-manager.texts.273') }}</p></div>
            <input type="text" maxlength="240" data-fm-prompt-input autocomplete="off">
            <small class="hidden" data-fm-prompt-error></small>
            <footer><button type="button" data-fm-prompt-cancel>{{ __('file-manager.texts.362') }}</button><button type="submit" class="is-primary" data-fm-prompt-confirm>{{ __('file-manager.texts.290') }}</button></footer>
        </form>
    </div>

    <div class="panel-file-manager__conflict hidden" data-fm-conflict role="dialog" aria-modal="true">
        <div class="panel-file-manager__conflict-card">
            <header><span><i class="ki-filled ki-information-4"></i></span><div><strong data-fm-conflict-title>{{ __('file-manager.texts.440') }}</strong><p data-fm-conflict-message></p></div></header>
            <div class="panel-file-manager__compare" data-fm-conflict-compare></div>
            <div class="panel-file-manager__usage-warning hidden" data-fm-conflict-usages></div>
            <footer data-fm-conflict-actions></footer>
        </div>
    </div>

    <div class="panel-file-manager__preview-modal hidden" data-fm-preview-modal role="dialog" aria-modal="true" aria-labelledby="fm-preview-title">
        <div class="panel-file-manager__preview-backdrop" data-fm-preview-close></div>
        <section class="panel-file-manager__preview-dialog">
            <header>
                <div class="panel-file-manager__preview-heading"><span><i class="ki-filled ki-eye"></i></span><div><strong id="fm-preview-title" data-fm-preview-title>{{ __('file-manager.texts.441') }}</strong><small data-fm-preview-meta></small></div></div>
                <div class="panel-file-manager__preview-head-actions">
                    <div class="panel-file-manager__audio-help hidden" data-audio-help>
                        <button type="button" data-audio-help-trigger aria-expanded="false" title="{{ __('file-manager.texts.442') }}"><i class="ki-filled ki-information-2"></i><span>{{ __('file-manager.texts.443') }}</span></button>
                        <div class="panel-file-manager__audio-help-menu hidden" data-audio-help-menu>
                            <header><i class="ki-filled ki-keyboard"></i><span><strong>{{ __('file-manager.texts.442') }}</strong><small>{{ __('file-manager.texts.444') }}</small></span></header>
                            <div><span><kbd>Space</kbd><b>{{ __('file-manager.texts.61') }}</b></span><span><kbd>← →</kbd><b>{{ __('file-manager.texts.62') }}</b></span><span><kbd>↑ ↓</kbd><b>{{ __('file-manager.texts.63') }}</b></span><span><kbd>M</kbd><b>{{ __('file-manager.texts.64') }}</b></span><span><kbd>R</kbd><b>{{ __('file-manager.texts.68') }}</b></span><span><kbd>[ ]</kbd><b>{{ __('file-manager.texts.69') }} A {{ __('file-manager.texts.70') }} B</b></span></div>
                            <p><i class="ki-filled ki-information-2"></i> {{ __('file-manager.texts.445') }} Player {{ __('file-manager.texts.446') }}</p>
                        </div>
                    </div>
                    <div class="panel-file-manager__zoom-tools hidden" data-fm-zoom-tools aria-label="{{ __('file-manager.texts.447') }}">
                        <button type="button" data-fm-zoom-out aria-label="{{ __('file-manager.texts.78') }}" title="{{ __('file-manager.texts.78') }}"><i class="ki-filled ki-minus"></i></button>
                        <output data-fm-zoom-value>{{ __('file-manager.texts.448') }}</output>
                        <button type="button" data-fm-zoom-in aria-label="{{ __('file-manager.texts.80') }}" title="{{ __('file-manager.texts.80') }}"><i class="ki-filled ki-plus"></i></button>
                        <button type="button" data-fm-zoom-fit aria-label="{{ __('file-manager.texts.449') }}" title="{{ __('file-manager.texts.449') }}"><i class="ki-filled ki-maximize"></i></button>
                    </div>
                    <a class="panel-file-manager__preview-download" data-fm-preview-download title="{{ __('file-manager.texts.87') }}"><i class="ki-filled ki-cloud-download"></i><span>{{ __('file-manager.texts.58') }}</span></a>
                    <button type="button" data-fm-preview-close aria-label="{{ __('file-manager.texts.450') }}" title="{{ __('file-manager.texts.50') }}"><i class="ki-filled ki-cross"></i></button>
                </div>
            </header>
            <nav class="panel-file-manager__sheet-tabs hidden" data-fm-sheet-tabs aria-label="{{ __('file-manager.texts.451') }} Excel"></nav>
            <div class="panel-file-manager__preview-stage" data-fm-preview-stage></div>
        </section>
    </div>
</section>
