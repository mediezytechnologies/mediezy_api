<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bannermodel extends Model
{
    use HasFactory;
    protected $table="user_banner";
    protected $fillable=['id','banner_title','banner_type','banner_image'];
}