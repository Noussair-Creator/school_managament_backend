<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Reservation; // <--- Corrected/Added import

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'picture',
        'phone',
        'address'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Reservations MADE BY this user (Teacher)
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // Reservations ACTIONED BY this user (Lab Manager)
    public function approvedReservations()
    {
        return $this->hasMany(Reservation::class, 'approved_by');
    }


    // Helper function to check if the user is a teacher
    public function isTeacher()
    {
        return $this->hasRole('teacher');
    }

    // Helper function to check if the user is a lab manager
    public function isLabManager()
    {
        return $this->hasRole('lab-manager'); // Assumes role name is 'lab-manager'
    }


    // // notifications relationships
    // public function notifications()
    // {
    //     return $this->hasMany(DatabaseNotification::class)
    //         ->orderBy('created_at', 'desc');
    // }

    // /**
    //  * Get the user's unread notifications.
    //  */
    // public function unreadNotifications()
    // {
    //     return $this->notifications()->whereNull('read_at');
    // }
}
