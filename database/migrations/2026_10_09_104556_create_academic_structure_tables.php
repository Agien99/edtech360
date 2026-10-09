
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Academic Sessions
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->string('status', 20)->default('planned');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Semesters
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedTinyInteger('number');
            $table->string('name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('planned');
            $table->timestamps();

            $table->unique(
                ['academic_session_id', 'number'],
                'semesters_session_number_unique'
            );

            $table->unique(
                ['id', 'academic_session_id'],
                'semesters_id_session_unique'
            );
        });

        // 3. Student Batches / Cohorts
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->unsignedSmallInteger('intake_year');
            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Classes
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_session_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('batch_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('code', 30);
            $table->string('name', 100);
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['academic_session_id', 'code'],
                'classes_session_code_unique'
            );

            $table->unique(
                ['id', 'academic_session_id'],
                'classes_id_session_unique'
            );

            $table->index('batch_id');
        });

        // 5. Subject Groups
        Schema::create('subject_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 6. Subjects
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subject_group_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('subject_groups');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_sessions');
    }
};