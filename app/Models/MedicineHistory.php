<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineHistory extends Model
{
    use HasFactory;
    protected $table = 'medicine_history';
    protected $primaryKey = 'history_id';
    protected $fillable = [
        'doctor_id',
        'medicine_id',
        'medicine_name',
        'created_at',
        'updated_at'
    ];
}
