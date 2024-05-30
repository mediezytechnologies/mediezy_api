<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineOrders extends Model
{
    use HasFactory;
    protected $table = 'medicine_orders';
    protected $fillable = [
        'order_id',
        'token_id',
        'patient_id',
        'user_id',
        'medical_shop_id',
        'doctor_id',
        'clinic_id',
        'order_details_status',
        'created_at',
        'updated_at'
    ];

}
