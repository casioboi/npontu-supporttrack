<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityUpdate extends Model
{
    protected $fillable = ['activity_id', 'user_id', 'personnel_name', 'personnel_email', 'personnel_role', 'status', 'remark', 'work_done', 'findings', 'outstanding_issues', 'next_steps', 'supporting_details'];
    public function activity() { return $this->belongsTo(Activity::class); }
    public function user() { return $this->belongsTo(User::class); }
}
