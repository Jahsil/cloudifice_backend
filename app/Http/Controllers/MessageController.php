<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Group;
use App\Events\MessageSent;
use App\Events\MessageRead;
use Illuminate\Support\Facades\Broadcast;

class MessageController extends Controller
{
    //
    public function sendMessage(Request $request)
    {
        //$user = $request->attributes->get("user");
        $user = $request->user()->id;

        $message = Message::create([
            'sender_id' => $user,
            'receiver_id' => $request->receiver_id,
            'group_id' => $request->group_id,
            'message' => $request->message,
            'file_path' => $request->file('file') ? $request->file('file')->store('files') : null,
	]);

        broadcast(new MessageSent($message))->toOthers();

	return response()->json(["status" => "OK", "message" => $message]);
    }

    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);
        $message->update(['read_at' => now()]);

        broadcast(new MessageRead($message))->toOthers();

        return response()->json($message);
    }

    public function getHistory($userId)
    {

        $messages = Message::where('sender_id', auth()->id())
            ->orWhere('receiver_id', auth()->id())
            ->orWhere('group_id', Group::whereHas('users', function ($query) {
                $query->where('user_id', auth()->id());
            })->pluck('id'))
            ->get();

        return response()->json($messages);
    }
}
