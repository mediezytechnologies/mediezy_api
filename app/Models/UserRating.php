<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class userRating extends Model
{
    use HasFactory;
    protected $table = 'user_rating';
    protected $fillable = [
        'user_rate',
        'rating_start',
        'rating_end',
        'created_at',
        'updated_at',
    ];

}
