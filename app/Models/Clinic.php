<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    use HasFactory;
    protected $table = 'clinics';
    protected $fillable = [
        'clinic_name', 'clinic_description', 'address', 'location',
        'clinic_start_time', 'clinic_end_time'
    ];
}
