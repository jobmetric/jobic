<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('posts:run-schedules')->everyMinute()->withoutOverlapping();
Schedule::command('kanban:send-reminders')->hourly()->withoutOverlapping();

Artisan::command('kanban:send-reminders', function (): void {
    $this->info((string) app(\App\Services\Posts\KanbanNotifications::class)->sendDeadlineReminders());
})->purpose('Notify assigned board members about upcoming editorial deadlines.');
