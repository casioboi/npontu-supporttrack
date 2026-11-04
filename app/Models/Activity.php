<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['title', 'description', 'activity_date', 'status', 'created_by', 'assigned_to'];
    protected function casts(): array { return ['activity_date' => 'date']; }
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) return $query;
        return $query->where(fn ($activities) => $activities
            ->where('assigned_to', $user->id)
            ->orWhere('created_by', $user->id));
    }
    public function scopeAssignedTo($query, User $user) { return $query->where('assigned_to', $user->id); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function updates() { return $this->hasMany(ActivityUpdate::class)->orderBy('created_at')->orderBy('id'); }
    public function latestUpdate() { return $this->hasOne(ActivityUpdate::class)->latestOfMany(); }
}
