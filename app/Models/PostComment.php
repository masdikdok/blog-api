<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id', 'post_id', 'user_id', 'content'
    ];

    public function child(){
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public function post(){
        return $this->belongsTo('App/Models/Post');
    }

    public function user(){
        return $this->belongsTo('App/Models/User');
    }
}
