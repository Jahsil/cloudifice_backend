<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\File as FileModel;
use App\Models\RecentFile as RecentFileModel; 
use Illuminate\Support\Facades\Log;


use Illuminate\Http\Request;

class FileController extends Controller
{
    //
     public function getAllFiles(Request $request)
    {
        try {
            DB::beginTransaction();

            $perPage = $request->query('per_page', 10); 
            $files = FileModel::join('users', 'files.owner_id', '=', 'users.id')
                            ->select('files.*',DB::raw("CONCAT(users.first_name, ' ', users.last_name) AS full_name"))
                            ->paginate();

            DB::commit();

            return response()->json([
                'status' => 'OK',
                'files' => $files
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Files fetch failed: ' . $e->getMessage());
            return response()->json(['error' => 'File fetch failed.'], 500);
        }
    }
}
