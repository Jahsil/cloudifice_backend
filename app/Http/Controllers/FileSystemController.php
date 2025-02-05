<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


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
            //  $rules = [
            //      'path' => 'required|string',
            //  ];
     
            //  $validator = Validator::make($request->query(), $rules);
     
            //  if ($validator->fails()) {
            //      return response()->json([
            //          'status' => 'error',
            //          'error' => $validator->errors(),
            //      ], 422);
            //  }
     
             $encodedURI = trim($request->query('path'));
             $path = urldecode($encodedURI);
     
             // Prevent directory traversal attacks
             if (strpos($path, '..') !== false || strpos($path, '/') === 0) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Invalid path provided.',
                 ], 400);
             }
     
             $rootPath = "/home";
             $username = $request->attributes->get("user")->username;
     
             // Construct the full search path
             $searchPath = realpath($rootPath . '/' . $username . '/' . $path . '/');
     
             // Ensure the search path exists and is within the allowed root directory
             if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'No file or directory found.',
                 ], 400);
             }
     
             // Get directory contents
             $contents = array_diff(scandir($searchPath), ['.', '..']); // Exclude . and ..
             Log::info("Contents of directory: " . json_encode($contents));
     
             // Process each item in the directory
             $data = [];
             foreach ($contents as $item) {
                Log::info("File----  $item");
                if(str_starts_with($item,".")){
                    continue;
                }
                 $itemPath = $searchPath . '/' . $item;
     
                 if (!file_exists($itemPath)) {
                     continue; // Skip if the item no longer exists
                 }
     
                 $stat = stat($itemPath);
                 $ownerInfo = posix_getpwuid($stat['uid']);
                 $groupInfo = posix_getgrgid($stat['gid']);
     
                 $data[] = [
                     'permissions' => substr(sprintf('%o', fileperms($itemPath)), -4), // File permissions
                     'owner' => $ownerInfo['name'] ?? 'unknown', // File owner
                     'group' => $groupInfo['name'] ?? 'unknown', // File group
                     'size' => is_dir($itemPath) ? "unknown" : $stat['size'], // Directory or file size
                     'date' => date('M d', $stat['mtime']), // Last modified date
                     'time' => date('H:i', $stat['mtime']), // Last modified time
                     'name' => $item, // File or directory name
                     'type' => is_dir($itemPath) ? 'directory' : 'file', // Type of file
                 ];
             }
     
             return response()->json([
                 'status' => 'OK',
                 'message' => 'File query successful.',
                 'result' => $data,
             ], 200);
     
         } catch (\Exception $e) {
             Log::error("Failed to access directory: " . $e->getMessage());
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to access directory.',
                 'error' => $e->getMessage(),
             ], 500);
         }
     }

     public function listArchive(Request $request)
     {
         try {
             $rootPath = "/home";
             $username = $request->attributes->get("user")->username;
             $path = "Archive";
     
             // Construct the full search path
             $searchPath = realpath($rootPath . '/' . $username . '/' . $path );
     
             // Ensure the search path exists and is within the allowed root directory
             if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'No file or directory found.',
                 ], 400);
             }
     
             // Get directory contents
             $contents = array_diff(scandir($searchPath), ['.', '..']); // Exclude . and ..
             Log::info("Contents of directory: " . json_encode($contents));
     
             // Process each item in the directory
             $data = [];
             foreach ($contents as $item) {
                Log::info("File----  $item");
                if(str_starts_with($item,".")){
                    continue;
                }
                 $itemPath = $searchPath . '/' . $item;
     
                 if (!file_exists($itemPath)) {
                     continue; // Skip if the item no longer exists
                 }
     
                 $stat = stat($itemPath);
                 $ownerInfo = posix_getpwuid($stat['uid']);
                 $groupInfo = posix_getgrgid($stat['gid']);
     
                 $data[] = [
                     'permissions' => substr(sprintf('%o', fileperms($itemPath)), -4), // File permissions
                     'owner' => $ownerInfo['name'] ?? 'unknown', // File owner
                     'group' => $groupInfo['name'] ?? 'unknown', // File group
                     'size' => is_dir($itemPath) ? "unknown" : $stat['size'], // Directory or file size
                     'date' => date('M d', $stat['mtime']), // Last modified date
                     'time' => date('H:i', $stat['mtime']), // Last modified time
                     'name' => $item, // File or directory name
                     'type' => is_dir($itemPath) ? 'directory' : 'file', // Type of file
                 ];
             }
     
             return response()->json([
                 'status' => 'OK',
                 'message' => 'File query successful.',
                 'result' => $data,
             ], 200);
     
         } catch (\Exception $e) {
             Log::error("Failed to access directory: " . $e->getMessage());
             return response()->json([
                 'status' => 'error',
                 'message' => 'Failed to access directory.',
                 'error' => $e->getMessage(),
             ], 500);
         }
     }

     public function listTrash(Request $request)
     {
         try {
             $rootPath = "/home";
             $username = $request->attributes->get("user")->username;
             $path = "Trash";
     
             // Construct the full search path
             $searchPath = realpath($rootPath . '/' . $username . '/' . $path );
     
             // Ensure the search path exists and is within the allowed root directory
             if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'No file or directory found.',
                 ], 400);
             }
     
             // Get directory contents
             $contents = array_diff(scandir($searchPath), ['.', '..']); // Exclude . and ..
             Log::info("Contents of directory: " . json_encode($contents));
     
             // Process each item in the directory
             $data = [];
             foreach ($contents as $item) {
                Log::info("File----  $item");
                if(str_starts_with($item,".")){
                    continue;
                }
                 $itemPath = $searchPath . '/' . $item;
     
                 if (!file_exists($itemPath)) {
                     continue; // Skip if the item no longer exists
                 }
     
                 $stat = stat($itemPath);
                 $ownerInfo = posix_getpwuid($stat['uid']);
                 $groupInfo = posix_getgrgid($stat['gid']);
     
                 $data[] = [
                     'permissions' => substr(sprintf('%o', fileperms($itemPath)), -4), // File permissions
                     'owner' => $ownerInfo['name'] ?? 'unknown', // File owner
                     'group' => $groupInfo['name'] ?? 'unknown', // File group
                     'size' => is_dir($itemPath) ? "unknown" : $stat['size'], // Directory or file size
                     'date' => date('M d', $stat['mtime']), // Last modified date
                     'time' => date('H:i', $stat['mtime']), // Last modified time
                     'name' => $item, // File or directory name
                     'type' => is_dir($itemPath) ? 'directory' : 'file', // Type of file
                 ];
             }
     
             return response()->json([
                 'status' => 'OK',
                 'message' => 'File query successful.',
                 'result' => $data,
             ], 200);
     
         } catch (\Exception $e) {
             Log::error("Failed to access directory: " . $e->getMessage());
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

        $rootPath = "/home";
        $username = $request->attributes->get("user")->username;
        
    
        $searchPath = $rootPath . '/' . $username . '/'  . $path;

     
        // Ensure the search path exists and is within the allowed root directory
        if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file or directory found.',
            ], 400);
        }

        $fullPath = "/home" . "/" . $username . "/" . $path . "/" . $name;

    
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
        
        $rootPath = "/home";
        $username = $request->attributes->get("user")->username;
        
    
        $searchPath = $rootPath . '/' . $username . '/'  . $path;

     
        // Ensure the search path exists and is within the allowed root directory
        if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file or directory found.',
            ], 400);
        }

        
        $fullPath = "/home" . "/" . $username . "/" . $path;
        $trashPath = "/home" . "/". $username . "/". "Trash";


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

    public function uploadFile2(Request $request){
        $rules = [
            'file' => 'required|file',
            'fileName' => 'required|string',
            'chunkIndex' => 'required|integer',
            'totalChunks' => 'required|integer',
            'path' => 'required|string'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors(),
            ], 422);
	    }

	

        $fileName = $request->fileName;
        $chunkIndex = $request->chunkIndex;
        $totalChunks = $request->totalChunks;
        $path = $request->path;
        

        $rootPath = "/home";
        $username = $request->attributes->get("user")->username;
        
    
        $searchPath = $rootPath . '/' . $username . '/'  . $path;

        // Ensure the search path exists and is within the allowed root directory
        if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file or directory found.',
            ], 400);
        }

        $fullPath = "/home" . "/" . $username . "/" . $path;
        $tempDir = "/home" . "/" . $username . "/". "tmp";

        if(!File::isDirectory($tempDir)){
            return response()->json([
                'status' => 'error',
                'message' => 'tmp directory does not exist.',
            ], 400);
        }

        if(!File::isDirectory($fullPath)){
            return response()->json([
                'status' => 'error',
                'message' => 'The destination directory does not exist.',
            ], 400);
        }


        // Save the chunk
        $chunkPath = $tempDir . "{$fileName}.part{$chunkIndex}";
        file_put_contents($chunkPath, file_get_contents($request->file('file')), FILE_APPEND);

        
        // Check if all chunks are received
        if ($this->allChunksReceived($fileName, $totalChunks, $tempDir)) {
            $finalPath = "/{$fullPath}/{$fileName}";
            $this->mergeChunks($fileName, $totalChunks, $tempDir, $finalPath);
        }

        return response()->json(['status' => 'OK','message' => 'Chunk uploaded successfully']);


    }

    private function allChunksReceived($fileName, $totalChunks, $tempDir)
    {
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!file_exists($tempDir . "{$fileName}.part{$i}")) {
                return false;
            }
        }
        return true;
    }

    private function mergeChunks($fileName, $totalChunks, $tempDir, $finalPath)
    {
        $finalFile = fopen($finalPath, 'w');
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $tempDir . "{$fileName}.part{$i}";
            fwrite($finalFile, file_get_contents($chunkPath));
            unlink($chunkPath); // Delete chunk after merging
        }
        fclose($finalFile);
    }

    public function checkChunks(Request $request){
        $rules = [
            'fileName' => 'required|string',           
            // 'path' => 'required|string'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors(),
            ], 422);
	    }

        $fileName = $request->fileName;

        $username = $request->attributes->get("user")->username;
        

        $tempDir = "/home" . "/". $username . "/". "tmp";

        if(!File::isDirectory($tempDir)){
            return response()->json([
                'status' => 'error',
                'message' => 'tmp directory does not exist.',
            ], 400);
        }

        $files = File::files($tempDir);
        $largestChunkIndex = 0;

        foreach ($files as $file){
            $filename = $file->getFilename();
            if (preg_match('/^(.*)\.part(\d+)$/', $filename, $matches)) {
                $baseName = $matches[1]; // This will be 'test.zip'
                $partNumber = (int) $matches[2];

                if ($baseName === $fileName && $partNumber > $largestChunkIndex) {
                    $largestChunkIndex = $partNumber;
                }
            }
        }

        return response()->json(['status' => 'OK','data' => $largestChunkIndex]);
    }

    public function viewFile(Request $request){
        

        $encodedURI = trim($request->query('path'));
        $path = urldecode($encodedURI);

        $rootPath = "/home";
        // $username = $request->attributes->get("user")->username;
        $username = "eyouel";

        // Construct the full search path
        $filePath = $rootPath . '/' . $username . '/' . $path;
       

        // Ensure the search path exists and is within the allowed root directory
        if (!$filePath || !File::exists($filePath) || strpos($filePath, $rootPath) !== 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file or directory found.',
            ], 400);
        }

        // Validate the file path
        if (!is_file($filePath) || !is_readable($filePath)) {
            abort(404, "File not found or inaccessible $filePath");
        }

        $fileName = basename($filePath);
        $mimeType = mime_content_type($filePath);
        $action = $request->query('action', 'view');

        $headers = [
            'Content-Type' => $mimeType,
        ];

        if ($action === 'download') {
            $headers['Content-Disposition'] = 'attachment; filename="' . $fileName . '"';
        } else {
            $headers['Content-Disposition'] = 'inline';
        }

        return response()->stream(function () use ($filePath) {
            readfile($filePath);
        }, 200, $headers);

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
