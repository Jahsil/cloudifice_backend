<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use App\Models\File as FileModel;
use App\Models\RecentFile as RecentFileModel; 
use App\Models\User; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;



use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Helper function to convert bytes to human-readable format
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return round($bytes / pow(1024, $power), $precision) . ' ' . $units[$power];
    }

    public function getTotalStats(Request $request)
    {
        $user = Auth::user();
        $username = $user->username;

        $directory = '/home' . DIRECTORY_SEPARATOR . $username;

        if (!File::exists($directory)) {
            return response()->json(['error' => 'Directory not found.'], 404);
        }

        $files = File::allFiles($directory);

        $stats = [
            'videos' => ['count' => 0, 'size' => 0],
            'images' => ['count' => 0, 'size' => 0],
            'documents' => ['count' => 0, 'size' => 0],
            'others' => ['count' => 0, 'size' => 0],
        ];

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            $size = $file->getSize(); // size in bytes

            if (in_array($ext, ['mp4', 'mkv', 'avi', 'mov', 'flv', 'wmv'])) {
                $stats['videos']['count']++;
                $stats['videos']['size'] += $size;
            } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                $stats['images']['count']++;
                $stats['images']['size'] += $size;
            } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'])) {
                $stats['documents']['count']++;
                $stats['documents']['size'] += $size;
            } else {
                $stats['others']['count']++;
                $stats['others']['size'] += $size;
            }
        }

        // Format size to readable form (e.g., MB, GB)
        foreach ($stats as &$group) {
            $group['size_human'] = $this->formatBytes($group['size']);
        }

        $storageSize = User::where('id', 1)->pluck('storage_limit');

        return response()->json([
            'total_files' => count($files),
            'breakdown' => $stats,
            'storage_size' => $storageSize 
        ]);
    }
   

}
