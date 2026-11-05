<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_assignees', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['activity_id', 'user_id']);
            $table->index(['user_id', 'activity_id']);
        });

        DB::table('activities')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->eachById(function (object $activity): void {
                DB::table('activity_assignees')->insert([
                    'activity_id' => $activity->id,
                    'user_id' => $activity->assigned_to,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        $hasAssignmentsNotRepresentableByLegacyColumn = DB::table('activity_assignees')
            ->join('activities', 'activities.id', '=', 'activity_assignees.activity_id')
            ->where(function ($query) {
                $query->whereNull('activities.assigned_to')
                    ->orWhereColumn('activity_assignees.user_id', '<>', 'activities.assigned_to');
            })
            ->exists();

        if ($hasAssignmentsNotRepresentableByLegacyColumn) {
            throw new RuntimeException(
                'Cannot roll back activity assignments while secondary assignees exist; preserve or migrate them before rollback.'
            );
        }

        Schema::dropIfExists('activity_assignees');
    }
};
