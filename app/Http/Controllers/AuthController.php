<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Cookie;





use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


use Illuminate\Support\Facades\DB;   
use Illuminate\Support\Facades\Log; 


class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        DB::beginTransaction(); 

        try {
            // Validate the request data
            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => 'required|string|max:255|unique:users',
                'nationality' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);

            // Create the user
            $user = User::create([
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'nationality' => $validatedData['nationality'],
                'password' => Hash::make($validatedData['password']),
            ]);

            // $token = JWTAuth::fromUser($user);

            DB::commit(); 

            return response()->json([
                'status' => 'OK',
                'user' => $user,
                'message' => 'User Registered Successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); 

            Log::error('Registration error: ' . $e->getMessage());

            // Return an error response
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // public function finishRegistration(Request $request){


    //     $rules = [
    //         'username' => 'required|string|alpha_dash',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'error' => $validator->errors(),
    //         ], 422);
    //     }


    //     try {
    //         $username = trim($request->input("username"));
    //         $rootPath = "/home/eyouel/Desktop";
    //         $userPath = "$rootPath/$username";
           
    //         // Check if the user directory already exists
    //         if (File::exists($userPath)) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'User already exists. Please use a different username.',
    //             ], 400);
    //         }

    //         File::makeDirectory($userPath, 0755, true);

    //         return response()->json([
    //             'status' => 'OK',
    //             'message' => 'User created successfully',
    //         ], 200);


    //     }
    //     catch (\Exception $e) {

    //         // Return an error response
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'User creation failed',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


    public function finishRegistration(Request $request) {

        $rules = [
            'username' => 'required|string|alpha_dash',
            'user_id' => 'required|integer'
        ];
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors(),
            ], 422);
        }
    
        $username = trim($request->input("username"));
        $user_id = trim($request->input("user_id"));
    
        // Check if user already exists
        $checkUser = new Process(["id", "-u", $username]);
        $checkUser->run();
    
        if ($checkUser->isSuccessful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'User already exists. Please use a different username.',
            ], 400);
        }

        try {
            $user = DB::table('users')
                ->where('id', $user_id)
                ->first();
        
            if ($user && $user->username !== null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User already exists',
                ], 500);
            }
        
        } catch (QueryException $e) {
            Log::error("User insert failed: " . $e->getMessage());
            return response()->json(['error' => 'Failed to create user'], 500);
        } catch (\Exception $e) {
            Log::error("Unexpected error: " . $e->getMessage());
            return response()->json(['error' => 'Something went wrong'], 500);
        }
        

    
        // Create the user
        $process = new Process(["sudo", "/usr/sbin/useradd", "-m", "-s", "/bin/bash", $username]);
        $process->run();
    
        if (!$process->isSuccessful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create user',
                'error' => $process->getErrorOutput(),
            ], 500);
        }

        // create trash 
        $trashPath = "/home" . "/" . $username . "/" . ".Trash";
        $trashFolder = new Process(["sudo", "/usr/bin/mkdir", "-p", $trashPath]);
        $trashFolder->run();

        if (!$trashFolder->isSuccessful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Trash already exists.',
                'error' => $trashFolder->getErrorOutput()
            ], 400);
        }

        // create tmp folder 
        $tmpPath = "/home" . "/" . $username . "/" . ".tmp";

        $tmpFolder = new Process(["sudo", "/usr/bin/mkdir","-p", $tmpPath]);
        $tmpFolder->run();

        if (!$tmpFolder->isSuccessful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'tmp already exists.',
                'error' => $tmpFolder->getErrorOutput()

            ], 400);
        }

        // create archive folder 
        $archivePath = "/home" . "/" . $username . "/" . ".Archive";

        $archiveFolder = new Process(["sudo", "/usr/bin/mkdir","-p", $archivePath]);
        $archiveFolder->run();

        if (!$archiveFolder->isSuccessful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Archive already exists.',
                'error' => $archiveFolder->getErrorOutput()

            ], 400);
        }

        try {
            // insert the username in the users table 
            $new_username = DB::table('users')
                ->where('id', $user_id)
                ->update([
                    'username' => $username
                ]);
            
        } catch (QueryException $e) {
            // Log the error for debugging
            Log::error("User insert failed: " . $e->getMessage());
    
            return response()->json(['error' => 'Failed to create user'], 500);
        } catch (\Exception $e) {
            // Catch any other errors
            Log::error("Unexpected error: " . $e->getMessage());
    
            return response()->json(['error' => 'Something went wrong'], 500);
        }


        return response()->json([
            'status' => 'OK',
            'message' => 'User created successfully',
           
        ], 200);
    }
    
    // Login a user
    public function login(Request $request)
    {
        DB::beginTransaction();  

        try {
            $credentials = $request->only('email', 'password');

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // $user = auth()->user();

            $customClaims = [
                'roles' => [],
            ];

            // $token = JWTAuth::claims($customClaims)->fromUser($user);

            DB::commit();

            // secure https -> http only -> 

            return response()->json(['status' => 'OK', 'message' => 'Login successful'])
                ->withCookie(cookie('token', $token, 60 * 24 * 30, '/', config('session.domain'), true, true, false, 'None'));

            // return response()->json(['status' => 'OK', 'message' => 'Login successful'])
            //     ->withCookie(cookie('token', $token, 60 * 24 * 30, '/', config('session.domain'), true, true, false, 'None')); // 👈 SameSite=None




            //  return response()->json(['status' => 'OK', 'message' => 'Login successful'])
            //      ->withCookie(cookie()->forever('token', $token, 0, '/', null, false, true, false, 'Strict'));

            return response()->json([
                'status' => 'OK',
                'message' => 'Login Successfull',
                // 'access_token' => $token,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();  
            Log::warning('Validation error during login: ' . $e->getMessage());

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();  
            Log::error('Login error: ' . $e->getMessage());

            return response()->json([
                'error' => 'Login failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    // Register User
    public function registerSanctum(Request $request)
    {
        try {
            $request->validate([
		'first_name' => 'required|string|max:255',
		'last_name' => 'required|string|max:255',
		'email' => 'required|string|email|max:255|unique:users',
		'phone' => 'required|string|max:15|unique:users',
		'nationality' => 'required|string',
                'password' => [
                    'required',
                    'string',
                    'min:8', // Minimum 8 characters
                    'regex:/[A-Z]/', // At least one uppercase letter
                    'regex:/[a-z]/', // At least one lowercase letter
                    'regex:/[0-9]/', // At least one number
                    'regex:/[@$!%*?&#]/', // At least one special character
                ],
            ]);

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'nationality' => $request->nationality,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                ],
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }

    public function loginSanctum(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Rate limiting key based on IP
            $key = 'login_attempts_' . $request->ip();

            // Check if too many login attempts
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json(['error' => 'Too many login attempts. Try again later.'], 429);
            }
                Log::info("Logging in ....... ");

            // Attempt to authenticate the user using the built-in session authentication
            if (Auth::attempt($request->only('email', 'password'))) {
                // Clear rate limiter on successful login
                RateLimiter::clear($key);

                // Regenerate the session ID to prevent session fixation attacks
                $request->session()->regenerate();

                $user = Auth::user();
                Log::info("Loggin user: ". json_encode($user));

		$token = $user->createToken('auth_token')->plainTextToken;

		$encodedToken = base64_encode($token);

                $cookie = Cookie::create('auth_token', $encodedToken)
                    ->withSecure(false) // Change to true if using HTTPS
                    ->withHttpOnly(false) // Allow JavaScript access
                    ->withSameSite('Lax')
		    ->withPath('/'); // Available on the entire domain

		$cookie = cookie('auth_token', base64_encode($token), 60 * 24, '/', null, false, false, false, 'Lax');


                // Return the user data (without token)
                return response()->json([
                    'user' => [
                        'id' => Auth::user()->id,
                        'first_name' => Auth::user()->first_name,
                        'last_name' => Auth::user()->last_name,
                        'username' => Auth::user()->username,
                        'email' => Auth::user()->email,
                    ],
                ], 200)->withCookie($cookie);
            }

            // Increment failed login attempts
            RateLimiter::hit($key, 60);
            Log::warning('Failed login attempt from IP: ' . $request->ip());

            throw ValidationException::withMessages([
                'message' => 'Invalid credentials',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 401);
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }


    // Logout User
    public function logoutSanctum(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }

    // Get Authenticated User
    public function userSanctum(Request $request)
    {
        try {
            $user = Auth::user();
            // return response()->json([
            //     "data" => $user
            // ]);
            // $user = DB::table('users')
            //     ->where('id', 1)
            //     ->first();
            return response()->json([
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'profile_image' => asset('storage/'.$user->profile_image)
                ]);

            // return response()->json([
            //     'id' => $request->user()->id,
            //     'first_name' => $request->user()->first_name,
            //     'last_name' => $request->user()->last_name,
            //     'username' => $request->user()->username,
            //     'email' => $request->user()->email,
            //     'profile_image' => asset('storage/'.$request->user()->profile_image)
            //     ]);

        } catch (\Exception $e) {
            Log::error('Fetch Auth User Error: ' . $e->getMessage());
            return response()->json(['error' => 'Could not fetch user details.'], 500);
        }
    }

    public function users()
    {
        try {
		
            $users = User::select("id", "first_name", "last_name", "phone", "username", "profile_image", "last_active_time")
            ->addSelect([
                'lastMessage' => Message::select("message")
                    ->whereColumn("sender_id", "users.id")
                    ->orWhereColumn("receiver_id", "users.id")
                    ->orderByDesc("created_at")
                    ->limit(1),
                'lastMessageTime' => Message::select("created_at")
                    ->whereColumn("sender_id", "users.id")
                    ->orWhereColumn("receiver_id", "users.id")
                    ->orderByDesc("created_at")
                    ->limit(1),
            ])
            ->get()
            ->map(function ($user) {
                $user->profile_image_url = $user->profile_image ? asset('storage/' . $user->profile_image) : null;
                return $user;
            });

	   
            return response()->json([
                "status" => "OK",
                "data" => $users
            ]);
        } catch (\Exception $e) {
            Log::error('Fetch Auth User Error: ' . $e->getMessage());
            return response()->json(['error' => 'Could not fetch user details.'], 500);
        }
    }
    
    // Get the authenticated user
    public function user()
    {
        DB::beginTransaction();  
    
        try {
            $user = Auth::user();
    
            if (!$user) {
                DB::rollBack();  
                return response()->json(['error' => 'Unauthorized'], 401);
            }
    
            DB::commit();  
    
            return response()->json([
                "status" => "OK", 
		"message" => [
		    "id" => $user->id,
                    "first_name" => $user->first_name,
                    "last_name" => $user->last_name,
                    "email" => $user->email,
                    "username" => $user->username,
                    "nationality" => $user->nationality,
                    "phone" => $user->phone,
                ]
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();  
            Log::error('Error fetching user: ' . $e->getMessage());
    
            return response()->json([
                'error' => 'Unable to fetch user',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    

    public function logout(Request $request)
    {
        try {
            $token = $request->cookie('token');

            if (!$token) {
                return response()->json(['error' => 'No token found'], 400);
            }

            JWTAuth::setToken($token)->invalidate();

            // Remove the cookie
            return response()->json(['status' => 'OK', 'message' => 'Successfully logged out'])
                ->withCookie(cookie()->forget('token', '/', config('session.domain'), true, true, false, 'None'));

            // return response()->json(['status'=>'OK', 'message' => 'Successfully logged out'])
            //     ->withCookie(cookie()->forget('token','/', config('session.domain'), true, true, false, 'None'));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to log out, token invalid'], 400);
        }
    }


    public function refreshToken(Request $request)
    {
        try {
            // Refresh the token
            $newToken = JWTAuth::refresh();
            return response()->json([
                'token' => $newToken,
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token refresh failed'], 401);
        }
    }

    public function allowNginxToAccessHomeDirectories(Request $request, $userId)
    {
        try {
            $user = User::where('id', $userId)
                ->first();
            
            $homeDirectory = "/home" . "/" . $user->username; 
            
            $nginxAccess = new Process(["sudo","chown", "-R", "www-data:www-data", $homeDirectory]);
            $nginxAccess->run();

            if ($nginxAccess->isSuccessful()) {
                return response()->json([
                    'status' => 'OK',
                    'message' => 'Utility directories created.',
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Cannot create utility directories.',
                'output' => $nginxAccess->getErrorOutput(),
            ], 500);
        } catch (\Exception $e) {
            Log::error("Nginx access change failed: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
