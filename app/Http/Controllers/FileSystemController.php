<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\TryCatch;

class FileSystemController extends Controller
{
    //
     // Helper function to calculate directory size
     private function getDirectorySize($directory)
     {
         $size = 0;
         foreach (File::allFiles($directory) as $file) {
             $size += $file->getSize();
         }
         return $size;
     }
     

     public function listFolders(Request $request)
     {
         try {
             // Validate the request
             $request->validate([
                 'path' => 'required|string',
             ]);
     
             $path = trim($request->query('path'));
     
             // Prevent invalid paths (e.g., paths with spaces or directory traversal attempts)
             if (strpos($path, '..') !== false || preg_match('/\s/', $path)) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Invalid path provided.',
                 ], 400);
             }
     
             $rootPath = "/home";
             $searchPath = realpath($rootPath . '/' . $path);
     
             // Ensure the search path exists and is within the allowed root directory
             if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'No file or directory found.',
                 ], 400);
             }
     
             // Get directory contents
             $contents = File::glob($searchPath . '/*');
     
             $data = array_map(function ($item) {
                 $stat = stat($item);
                 $ownerInfo = posix_getpwuid($stat['uid']); 
                 $groupInfo = posix_getgrgid($stat['gid']); 
     
                 return [
                     'permissions' => substr(sprintf('%o', fileperms($item)), -4), // File permissions
                     'owner' => $ownerInfo['name'] ?? 'unknown', // File owner
                     'group' => $groupInfo['name'] ?? 'unknown', // File group
                     'size' => is_dir($item) ? "unknown" : $stat['size'], // Directory or file size
                     'date' => date('M d', $stat['mtime']), // Last modified date
                     'time' => date('H:i', $stat['mtime']), // Last modified time
                     'name' => basename($item), // File or directory name
                     'type' => is_dir($item) ? 'directory' : 'file', // Type of file
                 ];
             }, $contents);
     
             return response()->json([
                 'status' => 'OK',
                 'message' => 'File query successful.',
                 'result' => $data,
             ], 200);
     
         } catch (\Exception $e) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to access directory.',
                 'error' => $e->getMessage(),
             ], 500);
         }
     }

     public function createFolder(Request $request){
 
        $rules = [
            'path' => 'required|string',
            'name' => 'required|string',
        ];

        $validator = Validator::make($request->query(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors(),
            ], 422);
        }
     
        $path = trim($request->query('path'));
        $name = trim($request->query('name'));
        
        
        $fullPath = "/home/eyouel/" . $path . "/" . $name;

    
        try {

            if(File::exists($fullPath)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'The directory already exists.',
                ], 400);
            }

            File::makeDirectory($fullPath, 0755, true);


            return response()->json([
                'status' => 'OK',
                'message' => 'Directory created successfully.',
                'path' => $fullPath,
            ], 201);


        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create directory.',
                'error' => $e->getMessage(),
            ], 500);
        }
     }  
     
    public function deleteFolder(Request $request){
        $trashPath = "/home/eyouel/Trash";

        $rules = [
            'path' => 'required|string'
        ];

        $validator = Validator::make($request->query(), $rules);

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors(),
            ], 422);
        }

        $path = trim($request->query('path'));        
        
        $fullPath = "/home/eyouel/" . $path;

       try {

        if(!File::isDirectory($fullPath)){
            return response()->json([
                'status' => 'error',
                'message' => 'The directory does not exist.',
            ], 400);
        }

        if(!File::isDirectory($trashPath)){
            return response()->json([
                'status' => 'error',
                'message' => 'Trash directory does not exist.',
            ], 400);
        }

        
        $destinationPath = $trashPath . '/' . basename($fullPath);

        if (!File::copyDirectory($fullPath, $destinationPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to copy directory to Trash.',
            ], 500);
        }

        File::deleteDirectory($fullPath);

        return response()->json([
            'status' => 'OK',
            'message' => 'Directory moved to trash successfully.',
            'trashPath' => $trashPath
        ], 201);

       } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create directory.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function uploadFile(Request $request){

        $rules = [
            'file' => 'required|file',
            'path' => 'required|string'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors(),
            ], 422);
        }

        try{

        $file = $request->file('file');
        $path = $request->input('path');

        if(!File::isDirectory($path)){
            return response()->json([
                'status' => 'error',
                'message' => 'The directory does not exist.',
            ], 400);
        }

        $destinationPath = '/home/eyouel/Desktop/Trash';
        $fileName = $file->getClientOriginalName();

        $file->move($destinationPath, $fileName);

        return response()->json([
            'message' => 'File uploaded successfully',
            'file_path' => $destinationPath . '/' . $fileName,
        ], 200);

       
            
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload file.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function runShellCommands(){

        try {

            chdir('/home/eyouel/Desktop');

            $output = [];
            $returnCode = null;

            exec('ls', $output, $returnCode);

            // dd($output, $returnCode);
            return response()->json(['status'=> 'OK','output'=>$output,'code'=>$returnCode]);

           
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to run command', 'message' => $e->getMessage()], 500);
        }

    }
}
