<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostSection extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'post_id', 'content'
    ];

    public function post(){
        return $this->belongsTo('App\Models\Post');
    }

}
