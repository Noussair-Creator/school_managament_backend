<?php

namespace App\Models;

use App\Models\ReservationMaterial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Reservation; // <--- Add import for Reservation

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        // 'identifier',
        'quantity_available', // Only if tracking stock
        'created_by',
    ];



    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reservations()
    {
        return $this->belongsToMany(Reservation::class, 'reservation_material')
            ->withPivot('quantity_requested');
    }
}
