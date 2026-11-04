<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function createdActivities() { return $this->hasMany(Activity::class, 'created_by'); }
    public function assignedActivities() { return $this->hasMany(Activity::class, 'assigned_to'); }
    public function activityUpdates() { return $this->hasMany(ActivityUpdate::class); }
    public function isAdmin(): bool { return $this->role === 'admin'; }
}
