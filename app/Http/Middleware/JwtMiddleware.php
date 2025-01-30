<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;


class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // bearer token based middleware 
        // try {
        //     $token = $request->bearerToken();

        //     if (!$token) {
        //         return response()->json(['error' => 'Token not provided'], 401);
        //     }

        //     $user = JWTAuth::parseToken()->authenticate();

        //     if (!$user) {
        //         return response()->json(['error' => 'User not found'], 401);
        //     }

        //     return $next($request);

        // } catch (JWTException $e) {
        //     return response()->json(['error' => 'Token is invalid or expired'], 401);
        // }

        // cookie based middlware

        try {
            $token = $request->cookie("token");

        if(!$token){
            return response()->json(['error' => 'Unauthorized - No token found'], Response::HTTP_UNAUTHORIZED);
        }

        // Manually set the token for JWTAuth
        JWTAuth::setToken($token);

        // Authenticate user
        $user = JWTAuth::authenticate();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized - User not found'], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('user', $user);

        return $next($request);
        
        }catch (JWTException $e) {
            return response()->json(['error' => 'Unauthorized - Invalid or expired token'], Response::HTTP_UNAUTHORIZED);
        }
        
    }
}
