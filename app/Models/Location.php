<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User; // Make sure User model is imported


class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    // --- Constants for Type ---
    public const TYPE_CLASSROOM = 'classroom';
    public const TYPE_LABORATORY = 'laboratory';
    public const TYPE_AMPHITHEATER = 'amphitheater';

    // --- Fillable Attributes ---
    // Removed 'reserved_by'
    protected $fillable = [
        'name',
        'capacity',
        'type',         // 'classroom', 'laboratory', or 'amphitheater'
        'created_by',   // ID of the Admin User who created this location entry
    ];


    // --- Relationships ---

    /**
     * User (Admin) who created the location record.
     */
    public function creator()
    {
        // Assumes 'created_by' column links to the 'users' table
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Location has many short-term reservations.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
    // (Keep getReservationsForTimeRange, scopeClassrooms, scopeLaboratories, scopeAmphitheaters as before)

    /**
     * Get all reservations for this location within a specific time range.
     * NOTE: Corrected overlap logic from previous example.
     */
    public function getReservationsForTimeRange($start_time, $end_time)
    {
        return $this->reservations()
            ->where(function ($query) use ($start_time, $end_time) {
                // Check for any overlap:
                // A reservation overlaps if it starts before the requested end time AND ends after the requested start time.
                $query->where('start_time', '<', $end_time)
                    ->where('end_time', '>', $start_time);
            })
            ->get();
    }

    /** Scope a query to only include classrooms. */
    public function scopeClassrooms(Builder $query): void
    {
        $query->where('type', self::TYPE_CLASSROOM);
    }
    /** Scope a query to only include laboratories. */
    public function scopeLaboratories(Builder $query): void
    {
        $query->where('type', self::TYPE_LABORATORY);
    }
    /** Scope a query to only include amphitheaters. */
    public function scopeAmphitheaters(Builder $query): void
    {
        $query->where('type', self::TYPE_AMPHITHEATER);
    }
}
