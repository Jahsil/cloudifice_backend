<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    //
    protected $fillable = [
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'owner_id'
    ];
}
