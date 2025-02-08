<?php

namespace App\Models;
use Illuminate\Notifications\Notifiable;


use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    //
    use Notifiable;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'group_id',
        'message',
        'file_path',
        'read_at',
    ];
}
