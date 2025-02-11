<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservations extends Model
{
    use HasFactory;

    protected $fillable = ['classroom_id', 'user_id', 'start_time', 'end_time'];

    /**
     * Get the classroom associated with this reservation.
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the teacher who made this reservation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
