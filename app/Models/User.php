<?php

namespace App\Models;
use Tymon\JWTAuth\Contracts\JWTSubject;


// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable 
{
    use HasApiTokens, HasFactory, Notifiable;

     protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'username',
        'phone',
        'nationality',
        'password',
    ];

   
    
   
}
