<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;


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
          
     	     $user = Auth::user();

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
             $username = $user->username;

            if(!$username){
                return response()->json([
                        'status' => 'error',
                        'message' => 'No username provided.',
                    ], 400);
            }
         
            // Construct the full search path
            $searchPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . $path);

                // If realpath() fails (e.g., path does not exist), construct a clean path manually
            if ($searchPath === false) {
                $searchPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                    trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                    ltrim($path, DIRECTORY_SEPARATOR);
                }
    
                // Ensure the search path exists and is within the allowed root directory
                if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No file or directory found.',
                    ], 400);
                }
        
                // Get directory contents
                $contents = File::files($searchPath); // Only files
                $directories = File::directories($searchPath); // Only directories

                $contents = array_merge($contents, $directories);

                // Process each item in the directory
                $data = [];
                foreach ($contents as $item) {
                    if(str_starts_with($item,".")){
                        continue;
                    }
                    $itemPath = $item;
        
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
                        'name' => basename($item), // File or directory name
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
     	     $user = Auth::user();

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
             $username = $user->username;
            // $username = "eyouel";

            $archivePath = "/home" . "/". $username . "/". "Archive";

            if(!File::isDirectory($archivePath)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Archive directory does not exist.',
                ], 400);
            }


            if(!$username){
                return response()->json([
                        'status' => 'error',
                        'message' => 'No username provided.',
                    ], 400);
            }

                
            $searchPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . 'Archive'. DIRECTORY_SEPARATOR . $path);

                // If realpath() fails (e.g., path does not exist), construct a clean path manually
            if ($searchPath === false) {
                $searchPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                            trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                            ltrim($path, DIRECTORY_SEPARATOR);
                }
    
                // Ensure the search path exists and is within the allowed root directory
                if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No file or directory found.',
                    ], 400);
                }
        
                // Get directory contents
        
                $contents = File::files($searchPath); // Only files
                $directories = File::directories($searchPath); // Only directories

                $contents = array_merge($contents, $directories);

                // Process each item in the directory
                $data = [];
                foreach ($contents as $item) {
                    if(str_starts_with($item,".")){
                        continue;
                    }
                    $itemPath = $item;
        
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
                        'name' => basename($item), // File or directory name
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
     	     $user = Auth::user();

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
            $username = $user->username;
            // $username = "eyouel";

            if(!$username){
                return response()->json([
                        'status' => 'error',
                        'message' => 'No username provided.',
                    ], 400);
            }

                
            $searchPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . 'Trash'. DIRECTORY_SEPARATOR . $path);

                // If realpath() fails (e.g., path does not exist), construct a clean path manually
            if ($searchPath === false) {
                $searchPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                            trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                            ltrim($path, DIRECTORY_SEPARATOR);
                }
    
            // Ensure the search path exists and is within the allowed root directory
            if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No file or directory found.',
                ], 400);
            }
        

            $contents = File::files($searchPath); // Only files
            $directories = File::directories($searchPath); // Only directories

            $contents = array_merge($contents, $directories);

             // Process each item in the directory
             $data = [];
             foreach ($contents as $item) {
                Log::info("File----  $item");
                if(str_starts_with($item,".")){
                    continue;
                }
                 $itemPath = $item;
     
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
                     'name' => basename($item), // File or directory name
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
        $user = Auth::user();

        $path = trim($request->query('path'));
        if($path === 'root'){
            $path = '/';
        }
        $name = trim($request->query('name'));

        $rootPath = "/home";
        $username = $user->username;
        // $username = "eyouel";
        

        $searchPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . $path);

        // If realpath() fails (e.g., path does not exist), construct a clean path manually
        if ($searchPath === false) {
            $searchPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        ltrim($path, DIRECTORY_SEPARATOR);
        }


     
        // Ensure the search path exists and is within the allowed root directory
        if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file or directory found.',
            ], 400);
        }

        // $fullPath = "/home" . "/" . $username . "/" . $path . "/" . $name;
        $fullPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $name);
        
        if ($fullPath === false) {
            $fullPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                            trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                ltrim($path). DIRECTORY_SEPARATOR . 
                trim($name, DIRECTORY_SEPARATOR);
            }

    
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
        
        $user = Auth::user();
        
        $rootPath = "/home";
        $username = $user->username;
        // $username = "eyouel";
        
    
        $searchPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . $path);

        // If realpath() fails (e.g., path does not exist), construct a clean path manually
        if ($searchPath === false) {
            $searchPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        ltrim($path, DIRECTORY_SEPARATOR);
        }

     
        // Ensure the search path exists and is within the allowed root directory
        if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file or directory found.',
            ], 400);
        }

        
        // $fullPath = "/home" . "/" . $username . "/" . $path;
        $fullPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . $path);

        // If realpath() fails (e.g., path does not exist), construct a clean path manually
        if ($fullPath === false) {
            $fullPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        ltrim($path, DIRECTORY_SEPARATOR);
        }


        $trashPath = "/home" . "/". $username . "/". "Trash";

        $trashPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . "Trash");

        // If realpath() fails (e.g., path does not exist), construct a clean path manually
        if ($trashPath === false) {
            $trashPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        ltrim("Trash", DIRECTORY_SEPARATOR);
        }

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

    public function deleteFile(Request $request){

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

        $user = Auth::user();
   
        
        $rootPath = "/home";
        $username = $user->username;
        // $username = "eyouel";
        
    
        // $searchPath = $rootPath . '/' . $username . '/'  . $path;
        $searchPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . $path);

        // If realpath() fails (e.g., path does not exist), construct a clean path manually
        if ($searchPath === false) {
            $searchPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        ltrim($path, DIRECTORY_SEPARATOR);
        }

     
        // Ensure the search path exists and is within the allowed root directory
        if (!$searchPath || !File::exists($searchPath) || strpos($searchPath, $rootPath) !== 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file or directory found.',
            ], 400);
        }

        
        // $fullPath = "/home" . "/" . $username . "/" . $path;
        $fullPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . $path);

        // If realpath() fails (e.g., path does not exist), construct a clean path manually
        if ($fullPath === false) {
            $fullPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        ltrim($path, DIRECTORY_SEPARATOR);
        }


        // $trashPath = "/home" . "/". $username . "/". "Trash";
        $trashPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . "Trash");

        // If realpath() fails (e.g., path does not exist), construct a clean path manually
        if ($trashPath === false) {
            $trashPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        ltrim("Trash", DIRECTORY_SEPARATOR);
        }

       try {

        if(!File::isFile($fullPath)){
            return response()->json([
                'status' => 'error',
                'message' => 'The file does not exist.',
            ], 400);
        }

        if(!File::isDirectory($trashPath)){
            return response()->json([
                'status' => 'error',
                'message' => 'Trash directory does not exist.',
            ], 400);
        }

        
        $destinationPath = $trashPath . '/' . basename($fullPath);

        if (!File::copy($fullPath, $destinationPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to copy file to Trash.',
            ], 500);
        }

        File::delete($fullPath);

        return response()->json([
            'status' => 'OK',
            'message' => 'File moved to trash successfully.',
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
        try {
            // $rules = [
            //     'file' => 'required|file',
            //     'fileName' => 'required|string',
            //     'chunkIndex' => 'required|integer',
            //     'totalChunks' => 'required|integer',
            //     'path' => 'required|string'
            // ];
    
            // $validator = Validator::make($request->all(), $rules);
    
            // if ($validator->fails()) {
            //     return response()->json([
            //         'status' => 'error',
            //         'error' => $validator->errors(),
            //     ], 422);
            // }
    
            $user = Auth::user();
    
            $fileName = $request->fileName;
            $chunkIndex = $request->chunkIndex;
            $totalChunks = $request->totalChunks;
            $path = $request->path;
    
            if($path === 'root'){
                $path = '/';
            }
    
            $rootPath = "/home";
            $username = $user->username;
    
            $searchPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . $path);
                if ($searchPath === false) {
                    $searchPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                                    trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                                    ltrim($path, DIRECTORY_SEPARATOR);
                }

                if (!$searchPath || strpos($searchPath, $rootPath) !== 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid path specified.',
                    ], 400);
                }
    
            $fullPath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . $path);
                if ($fullPath === false) {
                    $fullPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                                    trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                                    ltrim($path, DIRECTORY_SEPARATOR);
                }
    
            $tempDir = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . "tmp");
            if ($tempDir === false) {
                $tempDir = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                            trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                            ltrim("tmp", DIRECTORY_SEPARATOR);
            }

            // Check write permissions
        if (!is_writable($tempDir)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Temp directory is not writable.',
            ], 400);
        }

        if (!is_writable($fullPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Destination directory is not writable.',
            ], 400);
        }
    
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
            $chunkPath = $tempDir. DIRECTORY_SEPARATOR . "{$fileName}.part{$chunkIndex}";
            Log::info("Chunk path is ::: ". $chunkPath);
            file_put_contents($chunkPath, file_get_contents($request->file('file')), FILE_APPEND);
    
            // Check if all chunks are received
            if ($this->allChunksReceived($fileName, $totalChunks, $tempDir)) {
                $finalPath = "{$fullPath}/{$fileName}";
                $this->mergeChunks($fileName, $totalChunks, $tempDir, $finalPath);
            }
    
            return response()->json(['status' => 'OK','message' => 'Chunk uploaded successfully']);
    
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
    

    private function allChunksReceived($fileName, $totalChunks, $tempDir)
    {
        Log::warning("total chuncks path : {$totalChunks}");
        Log::warning("temp dir path : {$tempDir}");
        Log::warning("file name path : {$fileName}");

        for ($i = 0; $i < $totalChunks; $i++) {
            if (!file_exists($tempDir . DIRECTORY_SEPARATOR . "{$fileName}.part{$i}")) {
                Log::warning("return type is false");
                return false;
            }
        }
        Log::warning("return type is true");

        return true;
    }

    // private function mergeChunks($fileName, $totalChunks, $tempDir, $finalPath)
    // {
    //     $finalFile = fopen($finalPath, 'w');
    //     for ($i = 0; $i < $totalChunks; $i++) {
    //         $chunkPath = $tempDir . "{$fileName}.part{$i}";
    //         fwrite($finalFile, file_get_contents($chunkPath));
    //         unlink($chunkPath); // Delete chunk after merging
    //     }
    //     fclose($finalFile);
    // }
    private function mergeChunks($fileName, $totalChunks, $tempDir, $finalPath)
    {
        $finalFile = fopen($finalPath, 'wb'); // Use binary mode
        
        if ($finalFile === false) {
            throw new \Exception("Could not open final file for writing: {$finalPath}");
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . DIRECTORY_SEPARATOR . "{$fileName}.part{$i}";
                $chunkContent = file_get_contents($chunkPath);
                
                if ($chunkContent === false) {
                    throw new \Exception("Could not read chunk: {$chunkPath}");
                }
                
                if (fwrite($finalFile, $chunkContent) === false) {
                    throw new \Exception("Could not write chunk to final file: {$chunkPath}");
                }
                
                if (!unlink($chunkPath)) {
                    Log::warning("Could not delete chunk: {$chunkPath}");
                }
            }
        } finally {
            fclose($finalFile);
        }
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

        $user = Auth::user();

        $rootPath = "/home";
        $username = $user->username;

        $fileName = $request->fileName;
        $username = $user->username;
        // $username = "eyouel";
        

        // $tempDir = "/home" . "/". $username . "/". "tmp";
        $tempDir = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . "tmp");

        // If realpath() fails (e.g., path does not exist), construct a clean path manually
        if ($tempDir === false) {
            $tempDir = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        ltrim("tmp", DIRECTORY_SEPARATOR);
        }

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
        

        $user = Auth::user();
        Log::info('User is :::'. json_encode($user));

        $encodedURI = trim($request->query('path'));
        $path = urldecode($encodedURI);

        $rootPath = "/home";
        $username = $user->username;
        // $username = "eyouel";

        // Construct the full search path
        // $filePath = $rootPath . '/' . $username . '/' . $path;
        $filePath = realpath($rootPath . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . $path);

        // If realpath() fails (e.g., path does not exist), construct a clean path manually
        if ($filePath === false) {
            $filePath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        trim($username, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 
                        ltrim($path, DIRECTORY_SEPARATOR);
        }
       

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
    
    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        $user = Auth::user();
    
        // Delete old image if exists
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }
        $originalNameWithoutExtension = pathinfo($request->file('profile_image')->getClientOriginalName(), PATHINFO_FILENAME);

        $fileName = 'user_' . $user->id . '_' . $originalNameWithoutExtension . '.' .  $request->file('profile_image')->extension();

        // Store new image
        $path = Storage::disk('public')->putFileAs('profile_images', $request->file('profile_image'),$fileName);
    
        // Update database
        User::where('id', $user->id)
            ->update([
                'profile_image' => $path
            ]);
        //$user->update(['profile_image' => $path]);
    
        return response()->json([
            'message' => 'Profile image updated successfully',
            'profile_image' => asset("storage/{$path}")
        ],200);
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
