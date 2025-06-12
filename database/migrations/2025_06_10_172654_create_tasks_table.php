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
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->enum('status', ['backlog', 'todo', 'in_progress', 'in_review', 'done']);
            $table->integer('position');
            $table->foreignUuid('workspace_id')->constrained('workspaces')->onDelete('cascade');
            $table->foreignUuid('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignUuid('assignee_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
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
