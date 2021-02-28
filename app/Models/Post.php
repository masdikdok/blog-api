<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    const STATUS_INACTIVE = 1;
    const STATUS_ACTIVE = 1;

    use HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'title', 'slug', 'summary', 'published_at', 'status'
    ];

    public function user(){
        return $this->belongsTo('App/Models/User');
    }

    public function postSection(){
        return $this->hasMany('App/Models/PostSection', 'id');
    }

    public function mainSection(){
        return $this->hasOne('App/Models/PostSection', 'id')->orderBy('id');
    }

    public function postComment(){
        return $this->hasMany('App/Models/PostComment', 'id');
    }

    public function lastComment(){
        return $this->hasMany('App/Models/PostComment', 'id')
            ->whereNull('parent_id')
            ->orderBy('id', 'DESC')
            ->limit(3);
    }

    public function postViewer(){
        return $this->hasMany('App/Models/PostReviewer', 'id');
    }

    public function category(){
        return $this->hasOne('App/Models/Category', 'category_id');
    }
}
