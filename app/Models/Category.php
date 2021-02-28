<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    protected $fillable = [
        'parent_id', 'name', 'description', 'image', 'no_order', 'status'
    ];

    public function child(){
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public function grandChild(){
        return $this->child()->with('child');
    }

    public function getParentsNames() {
        if($this->parent) {
            return $this->parent->getParentsNames(). " > " . $this->name;
        } else {
            return $this->name;
        }
    }
}
