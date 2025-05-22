<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecentFile extends Model
{
    //
    protected $fillable = [
        'user_id',
        'file_id',
        'accessed_at'
    ];
}
