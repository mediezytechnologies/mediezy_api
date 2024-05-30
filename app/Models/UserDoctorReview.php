<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class UserDoctorReview extends Model
{
    use HasFactory;
    protected $table = 'user_doctor_review';
    protected $fillable = [
        'appointment_id',
        'review_id' ,
        "rating" ,
        'user_comments',
        'doctor_recommendation' ,
    ];
}


