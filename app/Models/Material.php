<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

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

    // Relationship to Reservations through the pivot table
    public function reservations()
    {
        // ARE THESE PARAMETERS CORRECT FOR YOUR SCHEMA?
        return $this->belongsToMany(Reservations::class, 'reservation_material', 'material_id', 'reservation_id')
            ->withPivot('quantity_requested') // Does 'quantity_requested' column exist?
            ->withTimestamps(); // ONLY if created_at/updated_at exist on pivot
    }
}
