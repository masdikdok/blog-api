<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostViewer extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id', 'user_id', 'ip_address', 'http_user_agent', 'description', 'created_at'
    ];
}
