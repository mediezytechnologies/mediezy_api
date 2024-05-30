<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    use HasFactory;
    use HasFactory;
    protected $table = 'suggestions';
    protected $primaryKey = 'suggestion_id';
    protected $fillable = [
        'message',
        'user_id',
        'type',
    ];
    
}
