<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recurring_task_id')->nullable()->constrained()->nullOnDelete();
            $table->date('task_date')->index();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->foreignId('assigned_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->boolean('requires_photo')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('completion_note', 500)->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamp('photo_pruned_at')->nullable();
            $table->timestamps();

            $table->unique(['recurring_task_id', 'task_date']);
            $table->index(['team_id', 'task_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
