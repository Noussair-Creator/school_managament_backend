<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    // Make sure these attributes are fillable
    protected $fillable = ['name', 'capacity', 'created_by', 'reserved_by'];

    /**
     * Superadmin who created the classroom.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    /**
     * Relationship: Classroom has many reservations.
     */
    public function reservations()
    {
        return $this->hasMany(Reservations::class);
    }

    /**
     * Get all reservations for a specific time range.
     */
    public function getReservationsForTimeRange($start_time, $end_time)
    {
        return $this->reservations()
            ->where(function ($query) use ($start_time, $end_time) {
                $query->whereBetween('start_time', [$start_time, $end_time])
                    ->orWhereBetween('end_time', [$start_time, $end_time]);
            })
            ->get();
    }
}
