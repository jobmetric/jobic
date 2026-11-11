<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('jobic.kanban.{type}',function(\App\Models\User $user,string $type):bool {
    if(!$user->status||!\JobMetric\Post\Facades\PostTypeRegistry::has($type))return false;
    $builder=\JobMetric\Post\Facades\PostTypeRegistry::for($type);
    return $builder->hasKanban()&&app(\App\Support\AdminAccess::class)->allows($user,$builder->getViewPermission() ?? ($type==='page'?'admin.content.pages.view':'admin.content.posts.view'));
});
