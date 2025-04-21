<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Material;

// Consider renaming to singular 'Reservation' if you prefer Laravel conventions
class Reservations extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled'; // Added for clarity
    protected $fillable = [
        'reservable_id',    // ID of the Location
        'reservable_type',  // Should be 'App\Models\Location'
        'user_id',          // ID of the User (Lab Manager) creating the reservation
        'start_time',
        'end_time',
        'teacher_id',       // ID of the User (Teacher) assigned to this slot (nullable?)
    ];

    /**
     * Date casts for convenience (Carbon instances).
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'approved_at' => 'datetime',
    ];

    // --- Relationships ---


    public function requestedMaterials()
    {
        // ARE THESE PARAMETERS CORRECT FOR YOUR SCHEMA?
        return $this->belongsToMany(Material::class, 'reservation_material', 'reservation_id', 'material_id')
            ->withPivot('quantity_requested') // Does 'quantity_requested' column exist?
            ->withTimestamps(); // ONLY if created_at/updated_at exist on pivot
    }

    // Relationship to Admin who approved/rejected
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Add scope for easy filtering by status
    public function scopeStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    // Add scope for pending reservations
    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    // Add scope for approved reservations
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', self::STATUS_APPROVED);
    }


    /**
     * Get the owning reservable model (which will be a Location instance).
     */
    public function reservable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user (Lab Manager) who created this reservation record.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the teacher assigned to use the location during this reservation.
     */
    public function teacher()
    {
        // Assumes 'teacher_id' column links to the 'users' table.
        // Make sure teacher_id is nullable in the database if it's optional.
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
