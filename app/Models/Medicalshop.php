<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicalshop extends Model
// {
//     use HasFactory;
//     protected $table="medicalshop";
//     protected $fillable = [
//         'id',
//         'firstname',
//         'shop_image',
//         'mobileNo',
//         'location',
//         'email',
//         'address',
//         'UserId',
//         'created_at',
//         'updated_at'];
// }

{
    use HasFactory;
    protected $table="medicalshop";
    protected $fillable = [
        'id',
        'firstname',
        'medicalshop_image',
        'mobileNo',
        'location',
        'email',
        'address',
        'pincode',
        'UserId',
'medical_shop_id',
        'created_at',
        'updated_at'
    ];
    public function tokenBookings()
    {
        return $this->hasMany(TokenBooking::class, 'medicalshop_id');
    }
    }
