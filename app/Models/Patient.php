<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;
    protected $table="patient";
    protected $fillable = [
        'id',
        'firstname',
        'lastname',
        'user_image',
        'mobileNo',
        'gender',
        'age',
        'dateofbirth', 
        'location',
        'email',
        'UserId',
        'created_at',
        'updated_at',
        'user_type',
        'regularMedicine',
        'illness',
        'Medicine_Taken',
        'allergy_id',
        'allergy_name',
        'surgery_name',
        'surgery_details',
        'treatment_taken',
        'treatment_taken_details'
    ];


     // Accessor for the 'username' attribute
     public function getGenderAttribute($value)
     {
         return ucwords($value);
     }

    //  public function tokenbookings()
    //  {
    //      return $this->hasMany(TokenBooking::class, 'token_id', 'token_id');
    //  }
}




