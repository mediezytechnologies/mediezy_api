<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment_image extends Model
{
    use HasFactory;
    protected $table = 'payment_image';

    protected $fillable = [
        'razorpay_image',
    ];
}
