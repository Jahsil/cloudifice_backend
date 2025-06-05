<?php

namespace App\Listeners;

use App\Events\PresenceChannelJoined;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OnlineUserEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    // public function handle(PresenceChannelJoined $event): void
    // {
    //     //
    // }

    public function handleJoin(PresenceChannelJoined $event)
    {
        $user = $event->user;
        $channel = $event->channelName;
        
        Log::info("User joined channel {$channel}: {$user->username} (ID: {$user->id})");
        
        // You can also:
        // - Update database status
        // - Broadcast to other users
        // - Cache the online users list
    }

    public function handleLeave(PresenceChannelLeft $event)
    {
        $user = $event->user;
        $channel = $event->channelName;
        
        Log::info("User left channel {$channel}: {$user->username} (ID: {$user->id})");
        
        // Perform cleanup or notifications
    }
}
