@php
    $conversations = [
        ['id'=>1,'name'=>'Kelly Marston','initials'=>'KM','role'=>'Product Manager','last'=>'20m','preview'=>'What time was our meeting scheduled for?','avatar'=>'12','status'=>'online','messages'=>[
            ['type'=>'incoming','avatar'=>'12','initials'=>'KM','texts'=>['Hey Erik, what time was our meeting scheduled for?'],'time'=>'Sent at 3:44pm'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>['It starts at 4:30pm. I already sent the agenda.'],'time'=>'Sent at 3:46pm'],
        ]],
        ['id'=>2,'name'=>'Alejandro Badajoz','initials'=>'AB','role'=>'Business Analyst','last'=>'24m','preview'=>'Nah, I have a meeting that starts in 5.','avatar'=>'36','status'=>'away','messages'=>[
            ['type'=>'incoming','avatar'=>'36','initials'=>'AB','texts'=>['Are you free for a quick review?','Nah, I have a meeting that starts in 5.'],'time'=>'Sent at 3:40pm'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>['No problem, send it over and I will leave comments.'],'time'=>'Sent at 3:42pm'],
        ]],
        ['id'=>3,'name'=>'Work Group','initials'=>'WG','role'=>'7 people are chatting','last'=>'31m','preview'=>'This is getting funnier and funnier. You gotta love dat team 🥰','avatar'=>null,'status'=>'online','group'=>true,'messages'=>[
            ['type'=>'incoming','avatar'=>'25','initials'=>'MW','texts'=>['Just found this marketing tool.'],'time'=>'Sent at 4:04pm'],
            ['type'=>'incoming','avatar'=>'25','initials'=>'MW','texts'=>['Features look nice. We could give it a try? Anyone want to review?'],'time'=>'Sent at 4:15pm'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>["Sure, I'll take a look real quick.",'I am also going to look at the available plans.'],'time'=>'Sent at 4:15pm'],
            ['type'=>'incoming','avatar'=>'9','initials'=>'AC','texts'=>["I know this one, we were using it in my previous company. It's reliable and fast 😊","I mean it's worth a try and we're in need right now."],'time'=>'Sent at 4:27pm'],
        ]],
        ['id'=>4,'name'=>'Alice Carasca','initials'=>'AC','role'=>'Software Engineer','last'=>'47m','preview'=>'I like the curves in this one.','avatar'=>'9','status'=>'online','messages'=>[
            ['type'=>'incoming','avatar'=>'9','initials'=>'AC','texts'=>['I pushed the new navigation prototype.','I like the curves in this one.'],'time'=>'Sent at 3:18pm'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>['The motion is subtle and the mobile state feels much cleaner.'],'time'=>'Sent at 3:23pm'],
        ]],
        ['id'=>5,'name'=>'Irina Vierbovsky','initials'=>'IV','role'=>'Project Manager','last'=>'56m','preview'=>'I need some help on something I am working on.','avatar'=>'23','status'=>'away','messages'=>[
            ['type'=>'incoming','avatar'=>'23','initials'=>'IV','texts'=>['I need some help on something I am working on.'],'time'=>'Sent at 3:02pm'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>['Of course. Share the document and I will check it today.'],'time'=>'Sent at 3:07pm'],
        ]],
        ['id'=>6,'name'=>'Mary Lebowski','initials'=>'ML','role'=>'Project Manager','last'=>'1h','preview'=>'Still down for that movie?','avatar'=>'5','status'=>'offline','messages'=>[
            ['type'=>'incoming','avatar'=>'5','initials'=>'ML','texts'=>['Still down for that movie?'],'time'=>'Sent at 2:42pm'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>['Absolutely. Same place at eight?'],'time'=>'Sent at 2:48pm'],
        ]],
        ['id'=>7,'name'=>'Esteban Castellanos','initials'=>'EC','role'=>'UI/UX Designer','last'=>'1h','preview'=>'I can send you the files.','avatar'=>'18','status'=>'online','messages'=>[
            ['type'=>'incoming','avatar'=>'18','initials'=>'EC','texts'=>['I can send you the files.','Do you need both desktop and mobile exports?'],'time'=>'Sent at 2:31pm'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>['Yes, both versions please.'],'time'=>'Sent at 2:34pm'],
        ]],
        ['id'=>8,'name'=>'Melany Wallace','initials'=>'MW','role'=>'Web Developer','last'=>'2h','preview'=>'I had some issues with the headers though.','avatar'=>'25','status'=>'online','messages'=>[
            ['type'=>'incoming','avatar'=>'25','initials'=>'MW','texts'=>['The implementation is almost ready.','I had some issues with the headers though.'],'time'=>'Sent at 1:55pm'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>['Let us review the responsive rules together.'],'time'=>'Sent at 2:01pm'],
        ]],
        ['id'=>9,'name'=>'Jimmy Hector','initials'=>'JH','role'=>'Project Manager','last'=>'3h','preview'=>'When are you available?','avatar'=>'21','status'=>'away','messages'=>[
            ['type'=>'incoming','avatar'=>'21','initials'=>'JH','texts'=>['When are you available?'],'time'=>'Sent at 12:48pm'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>['Any time after 3pm works for me.'],'time'=>'Sent at 12:51pm'],
        ]],
        ['id'=>10,'name'=>'Greta Kroppfer','initials'=>'GK','role'=>'Sales Manager','last'=>'3h','preview'=>'Thank you for your clean presentation, it was stunning.','avatar'=>'19','status'=>'offline','messages'=>[
            ['type'=>'incoming','avatar'=>'19','initials'=>'GK','texts'=>['Thank you for your clean presentation, it was stunning.'],'time'=>'Sent at 12:14pm'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>['Thank you Greta, glad the team enjoyed it.'],'time'=>'Sent at 12:19pm'],
        ]],
        ['id'=>11,'name'=>'Tara Svenson','initials'=>'TS','role'=>'UI/UX Designer','last'=>'9h','preview'=>'Hope you like them.','avatar'=>'13','status'=>'online','messages'=>[
            ['type'=>'incoming','avatar'=>'13','initials'=>'TS','texts'=>['I uploaded all the final illustrations. Hope you like them.'],'time'=>'Sent at 7:32am'],
            ['type'=>'outgoing','avatar'=>'22','initials'=>'EK','texts'=>['They look fantastic. The color palette is spot on.'],'time'=>'Sent at 7:45am'],
        ]],
    ];
    $selected = collect($conversations)->firstWhere('id', 3);
@endphp

<div class="messaging-v2-app" data-messaging-v2>
    <script type="application/json" data-messaging-data>@json($conversations)</script>
    <x-vuero.dashboard.apps.messaging.v2.header />
    <div class="messaging-v2-layout">
        <x-vuero.dashboard.apps.messaging.v2.conversations :conversations="$conversations" :selected="$selected" />
        <main class="messaging-v2-chat" data-v2-chat>
            <header class="messaging-v2-chat__header">
                <button type="button" data-v2-list-toggle aria-label="Open conversations"><x-vuero.icon name="menu" size="20" /></button>
                <h1 data-v2-title>{{ $selected['name'] }}</h1>
                <div class="messaging-v2-members" data-v2-members>
                    <x-vuero.dashboard.apps.messaging.avatar initials="MW" avatar="25" /><x-vuero.dashboard.apps.messaging.avatar initials="AC" avatar="9" /><x-vuero.dashboard.apps.messaging.avatar initials="EC" avatar="18" /><span>4</span>
                </div>
                <button type="button" class="messaging-v2-chat__details" data-v2-detail-toggle aria-label="Conversation details"><x-vuero.icon name="user" size="19" /></button>
            </header>
            <div class="messaging-v2-thread" data-v2-thread aria-live="polite"><x-vuero.dashboard.apps.messaging.v2.thread :conversation="$selected" /></div>
            <div class="messaging-chat-loader" data-messaging-loader aria-hidden="true"><span></span></div>
            <x-vuero.dashboard.apps.messaging.composer version="v2" />
        </main>
        <x-vuero.dashboard.apps.messaging.v2.detail :conversation="$selected" />
    </div>
    <button type="button" class="messaging-v2-overlay" data-v2-overlay aria-label="Close panel" tabindex="-1"></button>
</div>
