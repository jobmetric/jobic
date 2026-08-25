@props(['message'])

<section class="inbox-detail" data-inbox-detail aria-label="Selected message">
    <header class="inbox-header inbox-detail__header">
        <span class="inbox-avatar inbox-detail__avatar"><span>MW</span><img data-avatar-src="https://media.cssninja.io/content/avatars/{{ $message['avatar'] }}.jpg" alt="" data-inbox-detail-avatar hidden></span>
        <span class="inbox-detail__identity">
            <strong data-inbox-detail-subject>{{ $message['subject'] }}</strong>
            <small>&lt;<span data-inbox-detail-email>{{ $message['email'] }}</span>&gt;</small>
        </span>
        <span class="inbox-detail__attachment-count" data-inbox-detail-attachment-count><x-vuero.icon name="upload" size="15" /><b>{{ count($message['attachments']) }}</b></span>
        <div class="ui-dropdown inbox-dropdown" data-dropdown>
            <button type="button" class="inbox-action" data-dropdown-toggle aria-label="Selected message options" aria-haspopup="menu" aria-expanded="false"><x-vuero.icon name="more" size="18" /></button>
            <div class="ui-dropdown__menu inbox-dropdown__menu" data-dropdown-menu role="menu" hidden>
                <button type="button" role="menuitem" data-inbox-detail-menu="unread"><x-vuero.icon name="mail" size="16" /><span>Mark as unread</span></button>
                <button type="button" role="menuitem" data-inbox-detail-menu="archive"><x-vuero.icon name="content" size="16" /><span>Move to drafts</span></button>
                <button type="button" role="menuitem" data-inbox-detail-menu="delete"><x-vuero.icon name="trash" size="16" /><span>Delete message</span></button>
            </div>
        </div>
        <button type="button" class="inbox-action inbox-detail__close" data-inbox-detail-close aria-label="Close selected message"><x-vuero.icon name="x" size="19" /></button>
    </header>

    <div class="inbox-detail__scroll" data-inbox-detail-scroll>
        <article class="inbox-detail__message" data-inbox-detail-message>
            <header>
                <span><strong>Sent on</strong><small data-inbox-detail-date>{{ $message['sent_at'] }}</small></span>
                <div class="inbox-detail__actions">
                    <button type="button" class="inbox-action" data-inbox-message-action="reply" aria-label="Reply"><x-vuero.icon name="share" size="16" /></button>
                    <button type="button" class="inbox-action" data-inbox-message-action="note" aria-label="Create note"><x-vuero.icon name="content" size="16" /></button>
                    <button type="button" class="inbox-action" data-inbox-message-action="tag" aria-label="Tag message" aria-pressed="false"><x-vuero.icon name="ticket" size="16" /></button>
                    <button type="button" class="inbox-action" data-inbox-message-action="comment" aria-label="Add comment"><x-vuero.icon name="message" size="16" /></button>
                    <button type="button" class="inbox-action" data-inbox-message-action="lock" aria-label="Lock message" aria-pressed="false"><x-vuero.icon name="lock" size="16" /></button>
                </div>
            </header>
            <div class="inbox-detail__body">
                <p data-inbox-detail-greeting>{{ $message['greeting'] }}</p>
                <div data-inbox-detail-body>
                    @foreach ($message['body'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
                </div>
                <p>Sincerely,</p><p><span data-inbox-detail-sender>{{ $message['name'] }}</span> · Vuero Team</p>
            </div>
            <div class="inbox-attachments" data-inbox-attachments>
                @foreach ($message['attachments'] as $attachment)
                    <button type="button" data-inbox-download="{{ $attachment }}"><span>{{ $attachment }}</span><i><x-vuero.icon name="download" size="13" /></i></button>
                @endforeach
            </div>
        </article>

        <div class="inbox-reply">
            <form class="inbox-reply__bubble" data-inbox-reply-form>
                <header>
                    <img src="{{ asset('vuero/images/avatars/svg/vuero-1.svg') }}" alt="Erik K.">
                    <span><strong>Reply as</strong><small>erik@vuero.io</small></span>
                    <button type="button" class="inbox-action" aria-label="Reply options"><x-vuero.icon name="more" size="17" /></button>
                </header>
                <label><span class="sr-only">Reply message</span><textarea rows="6" placeholder="Type your message..." data-inbox-reply></textarea></label>
                <footer><span data-inbox-reply-status></span><button type="submit">Send Message</button></footer>
            </form>
        </div>
    </div>
</section>
