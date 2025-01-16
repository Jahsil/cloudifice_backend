<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

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


    // Login a user
    public function login(Request $request)
    {
        DB::beginTransaction();  

        try {
            $credentials = $request->only('email', 'password');

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            $user = auth()->user();

            $customClaims = [
                'roles' => [],
            ];

            $token = JWTAuth::claims($customClaims)->fromUser($user);

            DB::commit();  

            // return response()->json(['status' => 'OK', 'message' => 'Login successful'])
            //     ->withCookie(cookie()->forever('token', $token, 0, '/', null, false, true, false, 'Strict'));

            return response()->json([
                'status' => 'OK',
                'message' => 'Login Successfull',
                'access_token' => $token,
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
    
            return response()->json($user);
    
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
            // Invalidate the token
            JWTAuth::invalidate(JWTAuth::parseToken());

            return response()->json(['message' => 'Successfully logged out']);
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
}
