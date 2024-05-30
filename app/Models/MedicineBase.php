<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineBase extends Model
{
    use HasFactory;
    protected $table='medicine_base';
    protected $fillable = [
        'medicine_name',
        'manufacturers',
        'salt_composition',
        'description',
        'packaging',
        'mrp',
        'primary_use',
        'storage',
        'common_side_effect',
    ];
    
}