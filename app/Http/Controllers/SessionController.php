<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;


class SessionController extends Controller
{
    //
    public function checkToken(Request $request){

        $encrypted = 'eyJpdiI6ImdaV1pKOWVQbG55WkoxZXdET210TGc9PSIsInZhbHVlIjoiWjN1WXJRdUtIbkgxNzFXSGdXYWdBT0hGOG10clZzQjNhdm1GSmFMYm1iVEdtNlhrQmlRQldNQjY2VlE4V3RYOHJoSUJScm5aQmF2T0lWbXVPU1NhMWxTUHpsTkc1VGFwZjV1dmk0OWJRNVRCZUpoK0V5bmhGeERaajVOTFV2ZzciLCJtYWMiOiJjODU0MTE0ZTFiMTg0ZDAwYTY2MTc1NDliZTAwYTU1MjE3MmYyYmJlZmJlNjJkOGI0OTAwMjQyODFmOWFmNzFmIiwidGFnIjoiIn0%3D';
        
        // Decrypt and serialize
        $decrypted = Crypt::decrypt(urldecode($encrypted));
        $serialized = serialize($decrypted);
        $base64 = base64_encode($serialized);
    
        response()->json([
            "base64" => $base64
        ]);
    }
}
