<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLocations extends Model
{
    use HasFactory;
    protected $table = 'user_locations';
    protected $fillable = ['location_id','district','city','location_address', 'latitude', 'longitude', 'user_id'];
}