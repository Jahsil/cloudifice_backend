<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id; // Example authorization logic
});


Broadcast::channel('presence.online', function($user){
    return [
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'username' => $user->username
    ];
});