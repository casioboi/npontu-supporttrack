<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportIssueReport extends Model
{
    public const PRIORITIES = ['High', 'Medium', 'Low'];

    public const STATUSES = ['Open', 'In Progress', 'Resolved'];

    protected $fillable = [
        'title',
        'affected_service',
        'description',
        'priority',
        'status',
        'resolution_notes',
        'reported_by',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
