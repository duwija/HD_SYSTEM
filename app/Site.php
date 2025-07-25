<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    //
    use softDeletes;
    protected $fillable =['name','location','coordinate','created_at','description','deleted_at'];
}
