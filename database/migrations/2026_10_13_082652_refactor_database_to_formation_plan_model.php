<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('average_nodes', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('average_nodes')->restrictOnDelete();
            $table->decimal('weight', 3)->nullable();

            $table->index('parent_id');
        });

        DB::statement('ALTER TABLE average_nodes ADD CONSTRAINT average_nodes_no_self_parent_check CHECK (parent_id <> id)');
        DB::statement('ALTER TABLE average_nodes ADD CONSTRAINT average_nodes_weight_check CHECK (weight > 0 AND weight <= 1)');

        Schema::create('formation_plans_nodes', function (Blueprint $table) {
            $table->foreignId('formation_plan_id')->constrained('formation_plans')->cascadeOnDelete();
            $table->foreignId('average_node_id')->constrained('average_nodes')->cascadeOnDelete();
            $table->primary(['formation_plan_id', 'average_node_id']);

        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trainer_id');
            $table->foreignId('formation_plan_id')->constrained('formation_plans');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained('grades')->cascadeOnDelete();

            $table->dropColumn(['commentable_type', 'commentable_id']);
        });

        DB::statement('ALTER TABLE comments ADD CONSTRAINT comments_single_target_check CHECK ((project_id IS NULL) <> (grade_id IS NULL))');

        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('demo_path', 'demo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->renameColumn('demo_url', 'demo_path');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->string('commentable_type')->nullable();
            $table->unsignedBigInteger('commentable_id')->nullable();

            $table->index(['commentable_type', 'commentable_id']);
        });

        DB::table('comments')->whereNotNull('project_id')->update(['commentable_type' => 'project', 'commentable_id' => DB::raw('project_id')]);
        DB::table('comments')->whereNotNull('grade_id')->update(['commentable_type' => 'grade', 'commentable_id' => DB::raw('grade_id')]);

        Schema::table('comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('grade_id');

            $table->string('commentable_type')->nullable(false)->change();
            $table->unsignedBigInteger('commentable_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('formation_plan_id');
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index('trainer_id');
        });

        Schema::dropIfExists('formation_plans_nodes');

        Schema::table('average_nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('weight');
        });
    }
};
