<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = ['user_id', 'location_id', 'start_time', 'end_time', 'purpose', 'status', 'approved_by', 'approved_at', 'rejection_reason'];

    /**
     * Get the user that made the reservation (teacher).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location associated with the reservation.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the materials associated with the reservation.
     */
    public function materials()
    {
        return $this->belongsToMany(Material::class, 'reservation_material')
            ->withPivot('quantity_requested');
    }

    /**
     * Get the lab manager who approved/rejected the reservation.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
