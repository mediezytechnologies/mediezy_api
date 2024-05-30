<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFavMedicine extends Model
{
    use HasFactory;
    protected $table = "user_favorite_medicines";

    public function medicine()
    {
        return $this->belongsTo(MedicineBase::class, 'medicine_id', 'id');
    }
}
