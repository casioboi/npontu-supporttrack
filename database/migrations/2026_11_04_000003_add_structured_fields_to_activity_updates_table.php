<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activity_updates', function (Blueprint $table) {
            $table->text('work_done')->nullable()->after('remark');
            $table->text('findings')->nullable()->after('work_done');
            $table->text('outstanding_issues')->nullable()->after('findings');
            $table->text('next_steps')->nullable()->after('outstanding_issues');
        });
    }

    public function down(): void
    {
        Schema::table('activity_updates', function (Blueprint $table) {
            $table->dropColumn(['work_done', 'findings', 'outstanding_issues', 'next_steps']);
        });
    }
};
