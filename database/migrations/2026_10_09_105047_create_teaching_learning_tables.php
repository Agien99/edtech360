
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Teaching materials / notes
        Schema::create('teaching_materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_subject_class_id')
                ->constrained('teacher_subject_class')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->longText('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('material_type', 30)->default('notes');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['teacher_subject_class_id', 'status'],
                'materials_assignment_status_idx'
            );
        });

        // 2. Assignments / homework
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_subject_class_id')
                ->constrained('teacher_subject_class')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->longText('instructions')->nullable();

            $table->decimal('total_marks', 8, 2)
                ->nullable();

            $table->timestamp('available_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->boolean('allow_late_submission')
                ->default(false);

            $table->boolean('allow_file_submission')
                ->default(true);

            $table->string('status', 20)->default('draft');

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['teacher_subject_class_id', 'status'],
                'assignments_teaching_status_idx'
            );
        });

        // 3. Student assignment submissions
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
                ->constrained('assignments')
                ->restrictOnDelete();

            $table->foreignId('student_class_enrollment_id')
                ->constrained('student_class_enrollments')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('attempt_number')
                ->default(1);

            $table->longText('submission_text')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 30)->default('draft');

            $table->decimal('marks_awarded', 8, 2)
                ->nullable();

            $table->longText('teacher_feedback')->nullable();

            $table->foreignId('graded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('graded_at')->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'assignment_id',
                    'student_class_enrollment_id',
                    'attempt_number'
                ],
                'submission_attempt_unique'
            );

            $table->index(
                ['assignment_id', 'status'],
                'submissions_assignment_status_idx'
            );
        });

        
        // 4. Quiz identities
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_subject_class_id')
                ->constrained('teacher_subject_class')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->string('status', 20)->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['teacher_subject_class_id', 'status'],
                'quizzes_teaching_status_idx'
            );
        });

        // 5. Published and draft quiz versions
        Schema::create('quiz_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->restrictOnDelete();

            $table->unsignedInteger('version_number');

            $table->longText('instructions')->nullable();

            $table->unsignedSmallInteger('duration_minutes')
                ->nullable();

            $table->unsignedSmallInteger('max_attempts')
                ->default(1);

            $table->timestamp('available_at')->nullable();
            $table->timestamp('closes_at')->nullable();

            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('show_results')->default(true);

            // draft, published, archived
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                ['quiz_id', 'version_number'],
                'quiz_version_number_unique'
            );

            // For composite foreign keys
            $table->unique(
                ['id', 'quiz_id'],
                'quiz_version_id_quiz_unique'
            );

            $table->index(
                ['quiz_id', 'status'],
                'quiz_versions_status_idx'
            );
        });

        // 6. Version-specific questions
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_version_id')
                ->constrained('quiz_versions')
                ->restrictOnDelete();

            $table->longText('question_text');

            $table->string('question_type', 30)
                ->default('multiple_choice');

            $table->decimal('marks', 8, 2)->default(1);
            $table->unsignedInteger('sort_order')->default(0);

            $table->longText('explanation')->nullable();

            $table->timestamps();

            $table->unique(
                ['id', 'quiz_version_id'],
                'quiz_question_id_version_unique'
            );

            $table->index(
                ['quiz_version_id', 'sort_order'],
                'questions_version_order_idx'
            );
        });

        // 7. Question answer options
        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_question_id')
                ->constrained('quiz_questions')
                ->restrictOnDelete();

            $table->longText('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(
                ['id', 'quiz_question_id'],
                'quiz_option_id_question_unique'
            );

            $table->index(
                ['quiz_question_id', 'sort_order'],
                'options_question_order_idx'
            );
        });

        // 8. Student attempts tied to a specific version
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')
                ->constrained('quizzes')
                ->restrictOnDelete();

            $table->foreignId('quiz_version_id');

            $table->foreignId('student_class_enrollment_id')
                ->constrained('student_class_enrollments')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('attempt_number');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();

            $table->string('status', 30)
                ->default('in_progress');

            $table->timestamps();

            // Attempt's version belongs to its quiz
            $table->foreign(
                ['quiz_version_id', 'quiz_id'],
                'quiz_attempt_version_quiz_fk'
            )
                ->references(['id', 'quiz_id'])
                ->on('quiz_versions')
                ->restrictOnDelete();

            $table->unique(
                [
                    'quiz_id',
                    'student_class_enrollment_id',
                    'attempt_number'
                ],
                'quiz_attempt_number_unique'
            );

            $table->unique(
                ['id', 'quiz_version_id'],
                'quiz_attempt_id_version_unique'
            );

            $table->index(
                ['quiz_version_id', 'status'],
                'quiz_attempts_version_status_idx'
            );
        });

        // 9. Individual answers linked to attempted version
        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_attempt_id');
            $table->foreignId('quiz_version_id');
            $table->foreignId('quiz_question_id');
            $table->foreignId('quiz_option_id')->nullable();

            $table->longText('answer_text')->nullable();

            $table->decimal('marks_awarded', 8, 2)
                ->nullable();

            $table->boolean('is_correct')->nullable();
            $table->timestamp('answered_at')->nullable();

            $table->timestamps();

            // Answer belongs to the attempted version
            $table->foreign(
                ['quiz_attempt_id', 'quiz_version_id'],
                'quiz_answer_attempt_version_fk'
            )
                ->references(['id', 'quiz_version_id'])
                ->on('quiz_attempts')
                ->restrictOnDelete();

            // Question belongs to the same version
            $table->foreign(
                ['quiz_question_id', 'quiz_version_id'],
                'quiz_answer_question_version_fk'
            )
                ->references(['id', 'quiz_version_id'])
                ->on('quiz_questions')
                ->restrictOnDelete();

            // Selected option belongs to that question
            $table->foreign(
                ['quiz_option_id', 'quiz_question_id'],
                'quiz_answer_option_question_fk'
            )
                ->references(['id', 'quiz_question_id'])
                ->on('quiz_options')
                ->restrictOnDelete();

            $table->unique(
                ['quiz_attempt_id', 'quiz_question_id'],
                'quiz_answer_attempt_question_unique'
            );
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quiz_versions');
        Schema::dropIfExists('quizzes');

        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('teaching_materials');
    }

};