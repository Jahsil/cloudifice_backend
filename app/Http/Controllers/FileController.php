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

    public function getRecentFiles(Request $request){
        try {
            DB::beginTransaction();

            $recentFiles = 
                RecentFileModel::join('files', 'recent_files.file_id', '=', 'files.id')
                    ->join('users', 'recent_files.user_id', '=', 'users.id')
                    ->select('recent_files.accessed_at', 'files.file_type', 'files.file_size', 'files.file_name')
                    ->get();

            DB::commit();

            return response()->json([
                'status' => 'OK',
                'recent_files' => $recentFiles
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Files fetch failed: ' . $e->getMessage());
            return response()->json(['error' => 'File fetch failed.'], 500);
        }
    }
}
