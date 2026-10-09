
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Teacher profiles
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('staff_number', 50)->nullable()->unique();
            $table->string('full_name', 150);
            $table->string('phone', 30)->nullable();
            $table->date('joined_at')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Student profiles
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('student_number', 50)->unique();
            $table->string('full_name', 150);
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('status', 20)->default('active');

            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Student batch memberships
        Schema::create('student_batch', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_profile_id')
                ->constrained('student_profiles')
                ->restrictOnDelete();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->restrictOnDelete();

            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestamps();

            $table->unique(
                ['student_profile_id', 'batch_id'],
                'student_batch_membership_unique'
            );
        });

        // 4. Student class enrollments
        Schema::create('student_class_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_profile_id')
                ->constrained('student_profiles')
                ->restrictOnDelete();

            $table->foreignId('academic_session_id')
                ->constrained('academic_sessions')
                ->restrictOnDelete();

            $table->foreignId('class_id');
            $table->foreignId('semester_id');

            $table->date('enrolled_at');
            $table->date('ended_at')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestamps();

            // Class must belong to the selected session
            $table->foreign(
                ['class_id', 'academic_session_id'],
                'enrollments_class_session_fk'
            )
                ->references(['id', 'academic_session_id'])
                ->on('classes')
                ->restrictOnDelete();

            // Semester must belong to the same session
            $table->foreign(
                ['semester_id', 'academic_session_id'],
                'enrollments_semester_session_fk'
            )
                ->references(['id', 'academic_session_id'])
                ->on('semesters')
                ->restrictOnDelete();

            $table->index(
                ['student_profile_id', 'semester_id'],
                'student_enrollments_student_semester_idx'
            );

            $table->index(
                ['class_id', 'semester_id', 'status'],
                'student_enrollments_class_status_idx'
            );

            $table->index(
                ['student_profile_id', 'semester_id', 'enrolled_at', 'ended_at'],
                'student_enrollment_history_idx'
            );

            $table->unique(
                ['id', 'class_id', 'semester_id'],
                'enrollments_id_class_semester_unique'
            );
        });

        // 5. Class teacher responsibilities
        Schema::create('teacher_class_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_profile_id')
                ->constrained('teacher_profiles')
                ->restrictOnDelete();

            $table->foreignId('class_id')
                ->constrained('classes')
                ->restrictOnDelete();

            // Examples: class_teacher, assistant_class_teacher
            $table->string('position', 50);

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestamps();

            $table->index(
                ['class_id', 'position', 'status'],
                'teacher_class_position_idx'
            );

            $table->index(
                ['teacher_profile_id', 'status'],
                'teacher_class_teacher_status_idx'
            );
        });

        // 6. Subject teaching assignments
        Schema::create('teacher_subject_class', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_profile_id')
                ->constrained('teacher_profiles')
                ->restrictOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->restrictOnDelete();

            $table->foreignId('academic_session_id')
                ->constrained('academic_sessions')
                ->restrictOnDelete();

            $table->foreignId('class_id');
            $table->foreignId('semester_id');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestamps();

            // Class and semester must belong
            // to the same academic session
            $table->foreign(
                ['class_id', 'academic_session_id'],
                'teaching_class_session_fk'
            )
                ->references(['id', 'academic_session_id'])
                ->on('classes')
                ->restrictOnDelete();

            $table->foreign(
                ['semester_id', 'academic_session_id'],
                'teaching_semester_session_fk'
            )
                ->references(['id', 'academic_session_id'])
                ->on('semesters')
                ->restrictOnDelete();

            $table->index(
                ['teacher_profile_id', 'semester_id', 'status'],
                'teaching_teacher_semester_idx'
            );

            $table->index(
                ['class_id', 'subject_id', 'semester_id'],
                'teaching_class_subject_semester_idx'
            );

            $table->index(
                ['teacher_profile_id', 'semester_id', 'start_date', 'end_date'],
                'teaching_assignment_history_idx'
            );

            $table->unique(
                ['id', 'class_id', 'semester_id'],
                'teaching_id_class_semester_unique'
            );
        });

        // 7. Subject group membership and leadership
        Schema::create('subject_group_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subject_group_id')
                ->constrained('subject_groups')
                ->restrictOnDelete();

            $table->foreignId('teacher_profile_id')
                ->constrained('teacher_profiles')
                ->restrictOnDelete();

            $table->foreignId('academic_session_id')
                ->constrained('academic_sessions')
                ->restrictOnDelete();

            // Examples: coordinator, head, member
            $table->string('position', 50)->default('member');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active');

            $table->timestamps();

            $table->index(
                ['subject_group_id', 'academic_session_id', 'status'],
                'subject_group_session_status_idx'
            );

            $table->index(
                ['teacher_profile_id', 'status'],
                'subject_group_teacher_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_group_members');
        Schema::dropIfExists('teacher_subject_class');
        Schema::dropIfExists('teacher_class_assignments');
        Schema::dropIfExists('student_class_enrollments');
        Schema::dropIfExists('student_batch');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('teacher_profiles');
    }
};
