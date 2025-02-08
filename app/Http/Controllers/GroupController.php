<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\User;

class GroupController extends Controller
{
    //
    public function createGroup(Request $request)
    {
        $user = $request->attributes->get("user");

        $group = Group::create([
            'name' => $request->name,
            'creator_id' => $user->id,
        ]);

        $group->users()->attach($user->id);

        return response()->json($group);
    }

    public function addUserToGroup(Request $request, $groupId)
    {

        $group = Group::findOrFail($groupId);
        $user = User::findOrFail($request->user_id);

        $group->users()->attach($user->id);

        return response()->json($group);
    }
}
