<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;


class SessionController extends Controller
{
    //
    public function checkToken()
    {
        $encrypted = 'eyJpdiI6ImdaV1pKOWVQbG55WkoxZXdET210TGc9PSIsInZhbHVlIjoiWjN1WXJRdUtIbkgxNzFXSGdXYWdBT0hGOG10clZzQjNhdm1GSmFMYm1iVEdtNlhrQmlRQldNQjY2VlE4V3RYOHJoSUJScm5aQmF2T0lWbXVPU1NhMWxTUHpsTkc1VGFwZjV1dmk0OWJRNVRCZUpoK0V5bmhGeERaajVOTFV2ZzciLCJtYWMiOiJjODU0MTE0ZTFiMTg0ZDAwYTY2MTc1NDliZTAwYTU1MjE3MmYyYmJlZmJlNjJkOGI0OTAwMjQyODFmOWFmNzFmIiwidGFnIjoiIn0%3D';

        try {
            $decrypted = Crypt::decrypt(urldecode($encrypted));

            return response()->json([
                "decrypted" => $decrypted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "error" => "Failed to decrypt",
                "message" => $e->getMessage()
            ], 400);
        }
    }
}
