@php
    $conversations = [
        ['id'=>1,'name'=>'Henry Gibson','initials'=>'HG','role'=>'Product Manager','last'=>'3 minutes ago','avatar'=>'12','status'=>'online','unread'=>2,'messages'=>[
            ['type'=>'day','text'=>'TODAY'],
            ['type'=>'incoming','text'=>'Hey Erik, do you have a minute to review the product notes?','time'=>'10:24am'],
            ['type'=>'outgoing','text'=>'Sure. Send them over and I will take a look right now.','time'=>'10:26am'],
            ['type'=>'incoming','text'=>'Perfect, I just shared the final draft with you.','time'=>'10:27am'],
        ]],
        ['id'=>2,'name'=>'Melany Wallace','initials'=>'MW','role'=>'Web Developer','last'=>'30 minutes ago','avatar'=>'25','status'=>'online','unread'=>0,'messages'=>[
            ['type'=>'day','text'=>'MONDAY 23TH'],
            ['type'=>'incoming','text'=>'I was wondering why Danny still works on Sketch when Figma is now cheap and accessible. This could really boost our process.','time'=>'9:14am'],
            ['type'=>'outgoing','text'=>'Not really sure about why, but he certainly has good reasons. Anyway, why should he switch?','time'=>'9:18am'],
            ['type'=>'day','text'=>'YESTERDAY'],
            ['type'=>'incoming','text'=>'There are plenty of reasons, one of them being awesome UI kits being released every day.','time'=>'4:03pm'],
            ['type'=>'outgoing','text'=>'That really looks awesome! Danny should definitely have a look at this.','time'=>'4:08pm'],
        ]],
        ['id'=>3,'name'=>'Tara Svenson','initials'=>'TS','role'=>'UI/UX Designer','last'=>'1 day ago','avatar'=>'13','status'=>'away','unread'=>1,'messages'=>[
            ['type'=>'day','text'=>'YESTERDAY'],
            ['type'=>'incoming','text'=>'I exported the new mobile screens. The spacing feels much better now.','time'=>'1:46pm'],
            ['type'=>'outgoing','text'=>'Nice work. I especially like the lighter navigation and the new transitions.','time'=>'1:52pm'],
            ['type'=>'incoming','text'=>'Great! I will prepare the developer handoff today.','time'=>'1:54pm'],
        ]],
        ['id'=>4,'name'=>'Esteban Castellanos','initials'=>'EC','role'=>'UI/UX Designer','last'=>'1 day ago','avatar'=>'18','status'=>'offline','unread'=>0,'messages'=>[
            ['type'=>'day','text'=>'YESTERDAY'],
            ['type'=>'incoming','text'=>'The source files are ready. Do you want the compressed package too?','time'=>'11:20am'],
            ['type'=>'outgoing','text'=>'Yes please, that will be easier for the remote team.','time'=>'11:28am'],
        ]],
        ['id'=>5,'name'=>'Alice Carasca','initials'=>'AC','role'=>'Software Engineer','last'=>'2 days ago','avatar'=>'9','status'=>'online','unread'=>0,'messages'=>[
            ['type'=>'day','text'=>'SATURDAY'],
            ['type'=>'incoming','text'=>'The new API integration is now on staging and all checks are green.','time'=>'5:31pm'],
            ['type'=>'outgoing','text'=>'Excellent. I will test the conversation flow before we release it.','time'=>'5:38pm'],
        ]],
        ['id'=>6,'name'=>'Irina Vierbovsky','initials'=>'IV','role'=>'Project Manager','last'=>'3 days ago','avatar'=>'23','status'=>'away','unread'=>0,'messages'=>[
            ['type'=>'day','text'=>'FRIDAY'],
            ['type'=>'incoming','text'=>'I moved the review to Thursday so everyone can attend.','time'=>'3:12pm'],
            ['type'=>'outgoing','text'=>'Thursday works for me. Thanks for coordinating it.','time'=>'3:19pm'],
        ]],
    ];
    $selected = collect($conversations)->firstWhere('id', 2);
@endphp

<div class="messaging-v1-app" data-messaging-v1>
    <script type="application/json" data-messaging-data>@json($conversations)</script>
    <x-vuero.dashboard.apps.messaging.v1.toolbar />

    <aside class="messaging-v1-rail" aria-label="Recent conversations">
        <button type="button" class="messaging-v1-rail__toggle" data-v1-panel-toggle aria-label="Expand conversations" aria-expanded="false"><x-vuero.icon name="chevron-right" size="19" /></button>
        <button type="button" class="messaging-v1-rail__add" data-v1-new-conversation aria-label="New conversation"><x-vuero.icon name="plus" size="18" /></button>
        <div data-v1-rail-list>
            @foreach ($conversations as $conversation)
                <button type="button" class="{{ $conversation['id'] === $selected['id'] ? 'is-active' : '' }}" data-v1-conversation="{{ $conversation['id'] }}" aria-label="Open conversation with {{ $conversation['name'] }}">
                    <x-vuero.dashboard.apps.messaging.avatar :initials="$conversation['initials']" :avatar="$conversation['avatar']" :status="$conversation['status']" />
                    @if ($conversation['unread'])<b>{{ $conversation['unread'] }}</b>@endif
                </button>
            @endforeach
        </div>
    </aside>

    <x-vuero.dashboard.apps.messaging.v1.conversations :conversations="$conversations" :selected="$selected" />

    <section class="messaging-v1-chat" data-v1-chat>
        <header class="messaging-v1-chat__header">
            <button type="button" class="messaging-v1-chat__mobile-list" data-v1-mobile-list-open aria-label="Open conversations" aria-expanded="false"><x-vuero.icon name="menu" size="20" /></button>
            <div class="messaging-v1-chat__recipient">
                <x-vuero.dashboard.apps.messaging.avatar :initials="$selected['initials']" :avatar="$selected['avatar']" :status="$selected['status']" data-v1-header-avatar />
                <span><strong data-v1-header-name>{{ $selected['name'] }}</strong><small data-v1-header-role>{{ $selected['role'] }}</small></span>
            </div>
            <div class="messaging-v1-new" data-v1-new-form hidden>
                <span>To:</span><input type="search" placeholder="Start typing a name" data-v1-new-search><button type="button" data-v1-new-close aria-label="Close"><x-vuero.icon name="x" size="17" /></button>
                <div data-v1-new-results hidden></div>
            </div>
            <button type="button" class="messaging-v1-chat__info" data-v1-detail-open aria-label="Conversation details" aria-expanded="false"><x-vuero.icon name="user" size="19" /></button>
        </header>

        <div class="messaging-v1-chat__body">
            <ol class="messaging-v1-thread" data-v1-thread aria-live="polite">
                <x-vuero.dashboard.apps.messaging.v1.thread :conversation="$selected" />
            </ol>
            <div class="messaging-chat-loader" data-messaging-loader aria-hidden="true"><span></span></div>
            <x-vuero.dashboard.apps.messaging.v1.detail :conversation="$selected" />
        </div>

        <x-vuero.dashboard.apps.messaging.composer version="v1" />
    </section>
    <button type="button" class="messaging-v1-overlay" data-v1-overlay aria-label="Close panel" tabindex="-1"></button>
</div>
