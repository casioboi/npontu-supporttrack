<?php

namespace App\Models;

use Database\Factories\UserFactory;
use App\Notifications\PasswordAccessNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'must_change_password', 'email_verified_at'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean', 'must_change_password' => 'boolean'];
    }

    public function createdActivities() { return $this->hasMany(Activity::class, 'created_by'); }
    public function assignedActivities() { return $this->belongsToMany(Activity::class, 'activity_assignees')->withTimestamps(); }
    public function legacyAssignedActivities() { return $this->hasMany(Activity::class, 'assigned_to'); }
    public function activityUpdates() { return $this->hasMany(ActivityUpdate::class); }
    public function supportIssueReports() { return $this->hasMany(SupportIssueReport::class, 'reported_by'); }
    public function isAdmin(): bool { return $this->role === 'admin'; }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PasswordAccessNotification(
            $token,
            $this->must_change_password && $this->email_verified_at === null,
        ));
    }
}
