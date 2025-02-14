<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Group;
use App\Events\MessageSent;
use App\Events\MessageRead;
use Illuminate\Support\Facades\Broadcast;

use Illuminate\Support\Facades\DB;   
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\Auth;


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
        try {

	    $user = Auth::user();

	    DB::beginTransaction();

            $messages = Message::where(function ($query) use ($userId, $user) {
		    $query->where(function ($q) use ($user) {
		    	$q->where("sender_id", $user->id)
			   ->orWhere("receiver_id", $user->id);
		    })
		    ->where(function ($q) use ($userId){
		        $q->where("sender_id",$userId)
		 	   ->orWhere("receiver_id", $userId);
		    });
                })
                ->orderBy('created_at', 'desc') 
                ->paginate(10); 

            DB::commit();

            return response()->json([
                "status" => "OK",
                "data" => $messages
            ]);

        } catch (\Exception $e) {
            // Rollback in case of any failure
            DB::rollBack();

            return response()->json([
                'error' => 'Something went wrong!',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
