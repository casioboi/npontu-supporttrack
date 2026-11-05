<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_issue_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('affected_service')->nullable();
            $table->text('description');
            $table->string('priority')->default('Medium')->index();
            $table->string('status')->default('Open')->index();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['reported_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_issue_reports');
    }
};
