
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Student position definitions
        Schema::create('student_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->nullable()->unique();
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Class student positions
        Schema::create('class_student_positions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_class_enrollment_id')
                ->constrained('student_class_enrollments')
                ->restrictOnDelete();

            $table->foreignId('student_position_id')
                ->constrained('student_positions')
                ->restrictOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(
                ['student_position_id', 'end_date'],
                'class_positions_active_idx'
            );
        });

        // 3. Attendance sessions
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_session_id')
                ->constrained('academic_sessions')
                ->restrictOnDelete();

            $table->foreignId('class_id');
            $table->foreignId('semester_id');

            $table->date('attendance_date');

            $table->string('session_type', 30)
                ->default('daily');

            $table->foreignId('recorded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('status', 20)->default('draft');
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Class must belong to the academic session
            $table->foreign(
                ['class_id', 'academic_session_id'],
                'attendance_class_session_fk'
            )
                ->references(['id', 'academic_session_id'])
                ->on('classes')
                ->restrictOnDelete();

            // Semester must belong to the same session
            $table->foreign(
                ['semester_id', 'academic_session_id'],
                'attendance_semester_session_fk'
            )
                ->references(['id', 'academic_session_id'])
                ->on('semesters')
                ->restrictOnDelete();

            $table->unique(
                [
                    'class_id',
                    'semester_id',
                    'attendance_date',
                    'session_type'
                ],
                'attendance_session_unique'
            );

            $table->index(
                ['academic_session_id', 'attendance_date'],
                'attendance_session_date_idx'
            );

            $table->unique(
                ['id', 'class_id', 'semester_id'],
                'attendance_id_class_semester_unique'
            );
        });

        // 4. Individual attendance records
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_session_id');
            $table->foreignId('student_class_enrollment_id');

            // Shared context for composite foreign keys
            $table->foreignId('class_id');
            $table->foreignId('semester_id');

            $table->string('status', 30);
            $table->text('remarks')->nullable();

            $table->foreignId('recorded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            // Verify the attendance session's class and semester
            $table->foreign(
                ['attendance_session_id', 'class_id', 'semester_id'],
                'attendance_record_session_fk'
            )
                ->references(['id', 'class_id', 'semester_id'])
                ->on('attendance_sessions')
                ->restrictOnDelete();

            // Verify the student's enrollment matches
            $table->foreign(
                ['student_class_enrollment_id', 'class_id', 'semester_id'],
                'attendance_record_enrollment_fk'
            )
                ->references(['id', 'class_id', 'semester_id'])
                ->on('student_class_enrollments')
                ->restrictOnDelete();

            $table->unique(
                ['attendance_session_id', 'student_class_enrollment_id'],
                'attendance_record_unique'
            );
        });

        // 5. Class timetables        
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_session_id')
                ->constrained('academic_sessions')
                ->restrictOnDelete();

            $table->foreignId('class_id');
            $table->foreignId('semester_id');

            $table->string('name', 100);

            $table->date('effective_from');
            $table->date('effective_until')->nullable();

            $table->string('status', 20)->default('draft');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Validate class and academic session
            $table->foreign(
                ['class_id', 'academic_session_id'],
                'timetable_class_session_fk'
            )
                ->references(['id', 'academic_session_id'])
                ->on('classes')
                ->restrictOnDelete();

            // Validate semester and academic session
            $table->foreign(
                ['semester_id', 'academic_session_id'],
                'timetable_semester_session_fk'
            )
                ->references(['id', 'academic_session_id'])
                ->on('semesters')
                ->restrictOnDelete();

            $table->index(
                ['class_id', 'semester_id', 'status'],
                'timetable_class_semester_idx'
            );

            $table->index(
                ['academic_session_id', 'effective_from'],
                'timetable_session_effective_idx'
            );

            $table->unique(
                ['id', 'class_id', 'semester_id'],
                'timetable_id_class_semester_unique'
            );
        });

        // 6. Timetable entries
        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('timetable_id');
            $table->foreignId('teacher_subject_class_id');

            // Shared context for composite foreign keys
            $table->foreignId('class_id');
            $table->foreignId('semester_id');

            // 1 = Monday, 7 = Sunday
            $table->unsignedTinyInteger('day_of_week');

            $table->time('start_time');
            $table->time('end_time');

            $table->string('room', 100)->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Verify the timetable's class and semester
            $table->foreign(
                ['timetable_id', 'class_id', 'semester_id'],
                'timetable_entry_parent_fk'
            )
                ->references(['id', 'class_id', 'semester_id'])
                ->on('timetables')
                ->restrictOnDelete();

            // Verify the teaching assignment matches
            $table->foreign(
                ['teacher_subject_class_id', 'class_id', 'semester_id'],
                'timetable_entry_teaching_fk'
            )
                ->references(['id', 'class_id', 'semester_id'])
                ->on('teacher_subject_class')
                ->restrictOnDelete();

            $table->index(
                ['timetable_id', 'day_of_week', 'start_time'],
                'timetable_entries_schedule_idx'
            );
        });

        // 7. Co-curricular activities
        Schema::create('cocurricular_activities', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name', 150);

            // club, society, sport, uniformed_unit, other
            $table->string('category', 40);

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 8. Co-curricular teacher advisors
        Schema::create('cocurricular_advisors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cocurricular_activity_id')
                ->constrained('cocurricular_activities')
                ->restrictOnDelete();

            $table->foreignId('teacher_profile_id')
                ->constrained('teacher_profiles')
                ->restrictOnDelete();

            $table->foreignId('academic_session_id')
                ->constrained('academic_sessions')
                ->restrictOnDelete();

            // chief_advisor, assistant_advisor, advisor
            $table->string('position', 50)->default('advisor');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(
                ['cocurricular_activity_id', 'academic_session_id'],
                'cocurr_advisor_activity_session_idx'
            );
        });

        // 9. Student co-curricular memberships
        Schema::create('cocurricular_memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cocurricular_activity_id')
                ->constrained('cocurricular_activities')
                ->restrictOnDelete();

            $table->foreignId('student_profile_id')
                ->constrained('student_profiles')
                ->restrictOnDelete();

            $table->foreignId('academic_session_id')
                ->constrained('academic_sessions')
                ->restrictOnDelete();

            // member, president, secretary, treasurer, etc.
            $table->string('position', 100)->default('member');

            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(
                ['cocurricular_activity_id',
                 'student_profile_id', 'academic_session_id'],
                'cocurr_membership_unique'
            );
        });

        // 10. Co-curricular meetings and events
        Schema::create('cocurricular_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cocurricular_activity_id')
                ->constrained('cocurricular_activities')
                ->restrictOnDelete();

            $table->foreignId('academic_session_id')
                ->constrained('academic_sessions')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->text('description')->nullable();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();

            $table->string('location', 200)->nullable();
            $table->string('status', 20)->default('planned');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['cocurricular_activity_id', 'starts_at'],
                'cocurr_events_activity_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cocurricular_events');
        Schema::dropIfExists('cocurricular_memberships');
        Schema::dropIfExists('cocurricular_advisors');
        Schema::dropIfExists('cocurricular_activities');
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('timetables');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('class_student_positions');
        Schema::dropIfExists('student_positions');
    }
};