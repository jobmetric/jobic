@php
    $avatar = fn (string $name) => "/vuero/images/admin/business/avatars/{$name}.svg";
    $users = [
        ['name' => 'Yasseene Amzi', 'position' => 'Business Analyst', 'progress' => 18, 'picture' => $avatar('31')],
        ['name' => 'Mary Lebowski', 'position' => 'Project Manager', 'progress' => 22, 'picture' => $avatar('5')],
        ['name' => 'Erik Kovalsky', 'position' => 'Product Manager', 'progress' => -12, 'picture' => '/vuero/images/admin/avatars/vuero-1.svg'],
        ['name' => 'Jonathan Krugger', 'position' => 'Product Manager', 'progress' => 32, 'picture' => $avatar('32')],
        ['name' => 'Elizabeth Fisher', 'position' => 'Mobile Developer', 'progress' => -4, 'picture' => $avatar('21')],
        ['name' => 'Tara Svenson', 'position' => 'UI/UX Designer', 'progress' => 32, 'picture' => $avatar('13')],
    ];
    $todo = [
        ['title' => 'Call Mike R.', 'time' => 'at 8:30am', 'status' => 'Pending', 'completed' => false],
        ['title' => 'Finish document review', 'time' => 'at 11:30am', 'status' => 'Pending', 'completed' => true],
        ['title' => 'Project Meeting', 'time' => 'at 12:00pm', 'status' => 'Pending', 'completed' => false],
        ['title' => 'Meet with Kacey M.', 'time' => 'at 3:00pm', 'status' => 'Pending', 'completed' => false],
        ['title' => 'Review final draft', 'time' => 'at 6:00pm', 'status' => 'Pending', 'completed' => false],
    ];
    $filesRecent = [
        ['name' => 'theme.zip', 'type' => 'Zipped Folder', 'icon' => 'box', 'color' => 'info'],
        ['name' => 'project_outline.doc', 'type' => 'MS Word file', 'icon' => 'content', 'color' => 'purple'],
        ['name' => 'requirements_v3.pdf', 'type' => 'PDF file', 'icon' => 'content', 'color' => 'orange'],
        ['name' => 'project_budget.xsls', 'type' => 'MS Excel file', 'icon' => 'content', 'color' => 'info'],
        ['name' => 'wireframes.ai', 'type' => 'Illustrator file', 'icon' => 'content', 'color' => 'warning'],
        ['name' => 'invoice_recap.xsls', 'type' => 'MS Excel file', 'icon' => 'content', 'color' => 'info'],
    ];
    $filesPopular = [
        ['name' => 'header.ai', 'type' => 'Illustrator file', 'icon' => 'content', 'color' => 'warning'],
        ['name' => 'section.ai', 'type' => 'Illustrator file', 'icon' => 'content', 'color' => 'warning'],
        ['name' => 'footer.ai', 'type' => 'Illustrator file', 'icon' => 'content', 'color' => 'warning'],
        ['name' => 'theme_assets.zip', 'type' => 'Zipped Folder', 'icon' => 'box', 'color' => 'info'],
        ['name' => 'team_structure.doc', 'type' => 'MS Word file', 'icon' => 'content', 'color' => 'purple'],
        ['name' => 'meeting_notes.doc', 'type' => 'MS Word file', 'icon' => 'content', 'color' => 'purple'],
    ];
    $topics = [
        ['title' => 'Browsers', 'category' => 'Technology', 'icon' => 'chrome'],
        ['title' => 'Natural Ecosystems', 'category' => 'Environment', 'icon' => 'droplet'],
        ['title' => 'Computer Chips', 'category' => 'Technology', 'icon' => 'cpu'],
        ['title' => 'Modal Improvisation', 'category' => 'Music', 'icon' => 'media'],
        ['title' => 'Old Movies', 'category' => 'Entertainment', 'icon' => 'video'],
        ['title' => 'Git Management', 'category' => 'Technology', 'icon' => 'brand-github'],
    ];
    $timeline = [
        ['title' => 'Call from Kendra', 'text' => 'A few minutes ago', 'time' => '10:30', 'icon' => 'phone', 'color' => 'primary'],
        ['title' => 'Message from Alice', 'text' => 'Can you send me the files?', 'time' => '10:16', 'icon' => 'message', 'color' => 'info'],
        ['title' => 'New mail', 'text' => 'From john.w@vuero.io', 'time' => '09:47', 'icon' => 'mail', 'color' => 'warning'],
        ['title' => 'Project review', 'text' => 'Project Atlas has been updated', 'time' => '09:21', 'icon' => 'edit', 'color' => 'purple'],
        ['title' => 'New member', 'text' => 'Tina joined the team', 'time' => '08:56', 'icon' => 'user-plus', 'color' => 'success'],
    ];
@endphp

<x-vuero.widgets.page :dashboard="$dashboard" :script="$dashboardScript">
    <div class="widget-grid widget-grid--three">
        <x-vuero.widgets.card title="User Progress" class="widget-list-card"><x-vuero.widgets.user-list :users="$users" /></x-vuero.widgets.card>
        <x-vuero.widgets.card title="User Progress" class="widget-list-card"><x-vuero.widgets.user-list :users="$users" squared /></x-vuero.widgets.card>
        <x-vuero.widgets.card class="widget-list-card widget-card--tabbed">
            <x-slot:toolbar><h2>Files</h2><x-vuero.widgets.tabs name="files" /></x-slot:toolbar>
            <div data-widget-panel="files:0"><x-vuero.widgets.file-list :files="$filesRecent" /></div>
            <div data-widget-panel="files:1" hidden><x-vuero.widgets.file-list :files="$filesPopular" /></div>
        </x-vuero.widgets.card>

        <x-vuero.widgets.card title="Todo" class="widget-list-card"><x-vuero.widgets.todo-list :items="$todo" color="warning" /></x-vuero.widgets.card>
        <x-vuero.widgets.card title="Todo" class="widget-list-card"><x-vuero.widgets.todo-list :items="$todo" color="info" /></x-vuero.widgets.card>
        <x-vuero.widgets.card class="widget-list-card widget-card--tabbed">
            <x-slot:toolbar><h2>Todo</h2><x-vuero.widgets.tabs name="todo" :labels="['All', 'Mine']" /></x-slot:toolbar>
            <div data-widget-panel="todo:0"><x-vuero.widgets.todo-list :items="$todo" /></div>
            <div data-widget-panel="todo:1" hidden><x-vuero.widgets.todo-list :items="array_slice($todo, 1, 3)" /></div>
        </x-vuero.widgets.card>

        <x-vuero.widgets.card title="Topics" class="widget-list-card"><x-vuero.widgets.topic-list :topics="$topics" rounded /></x-vuero.widgets.card>
        <x-vuero.widgets.card title="Topics" class="widget-list-card"><x-vuero.widgets.topic-list :topics="$topics" /></x-vuero.widgets.card>
        <x-vuero.widgets.card class="widget-list-card widget-card--tabbed">
            <x-slot:toolbar><h2>Topics</h2><x-vuero.widgets.tabs name="topics" /></x-slot:toolbar>
            <div data-widget-panel="topics:0"><x-vuero.widgets.topic-list :topics="$topics" /></div>
            <div data-widget-panel="topics:1" hidden><x-vuero.widgets.topic-list :topics="array_reverse($topics)" /></div>
        </x-vuero.widgets.card>

        <x-vuero.widgets.card title="Timeline" class="widget-list-card"><x-vuero.widgets.timeline :items="$timeline" /></x-vuero.widgets.card>
        <x-vuero.widgets.card title="Timeline" class="widget-list-card"><x-vuero.widgets.timeline :items="$timeline" squared /></x-vuero.widgets.card>
        <x-vuero.widgets.card title="Timeline" class="widget-list-card"><x-vuero.widgets.timeline :items="$timeline" squared colored /></x-vuero.widgets.card>
    </div>
</x-vuero.widgets.page>
