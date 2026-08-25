@props(['message', 'selected' => false])

<article
    class="inbox-message-card {{ $selected ? 'is-selected' : '' }} {{ $message['unread'] ? 'is-unread' : '' }}"
    tabindex="0"
    role="button"
    data-inbox-message="{{ $message['id'] }}"
    data-inbox-mailboxes="{{ implode(',', $message['mailboxes']) }}"
    data-inbox-search-text="{{ mb_strtolower($message['name'].' '.$message['email'].' '.$message['subject']) }}"
    aria-label="Open message from {{ $message['name'] }}"
>
    <label class="inbox-checkbox" aria-label="Select message from {{ $message['name'] }}">
        <input type="checkbox" value="{{ $message['id'] }}" data-inbox-message-check>
        <span><x-vuero.icon name="check" size="12" /></span>
    </label>
    <span class="inbox-message-card__meta">
        <strong>{{ $message['name'] }}</strong>
        <small>&lt;{{ $message['email'] }}&gt;</small>
        <span>{{ $message['subject'] }}</span>
    </span>
    <time>{{ $message['date'] }}</time>
</article>
