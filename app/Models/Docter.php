<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Docter extends Model
{
    use HasFactory;
    protected $table = 'docter';
    protected $fillable = [
        'id',
        'firstname',
        'lastname',
        'docter_image',
        'mobileNo',
        'gender',
        'location',
        'email',
        'specialization_id',
        'specification_id',
        'subspecification_id',
        'about',
        'Services_at',
        'UserId',
        'created_at',
        'updated_at',
        'HospitalId'
    ];


    public function appointments()
    {
        return $this->hasMany(TokenBooking::class, 'doctor_id', 'UserId');
    }

    public function specialization()
    {
        return $this->hasOne(Specialize::class, 'id', '	specialization_id');
    }
    public function availability()
    {
        return $this->hasMany(DocterAvailability::class);
    }
    public function tokencount()
    {
        return $this->hasMany(NewTokens::class, 'doctor_id');
    }
    public function specializations()
    {
        return $this->belongsTo(Specialize::class, 'specialization_id');
    }

    public function clinics()
{
    return $this->belongsToMany(Clinic::class, 'doctor_clinic_relations', 'doctor_id', 'clinic_id', 'id', 'clinic_id');
}

}
