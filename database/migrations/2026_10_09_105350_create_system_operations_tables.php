
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Central audit logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Historical identity retained independently
            $table->string('actor_identifier', 150)->nullable();

            $table->string('actor_role_snapshot', 255)->nullable();
            $table->string('event_category', 50)->default('system');

            // create, update, delete, login, download, etc.
            $table->string('action', 100);

            // e.g. attendance, assignments, users, files
            $table->string('module', 100);

            // Polymorphic target
            $table->nullableMorphs('auditable');

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_id', 100)->nullable();

            $table->string('outcome', 30)->default('success');
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['event_category', 'created_at'],
                'audit_category_date_idx'
            );

            $table->index(
                ['module', 'action', 'created_at'],
                'audit_module_action_date_idx'
            );

            $table->index(
                ['actor_user_id', 'created_at'],
                'audit_actor_date_idx'
            );

            $table->index('request_id');
        });

        // 2. Logical file attachments
        Schema::create('file_attachments', function (Blueprint $table) {
            $table->id();

            // Owner: teaching material, assignment, submission, etc.
            $table->morphs('attachable');

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('display_name', 255);
            $table->string('purpose', 50)->default('general');

            // Access enforced by parent record and policies
            $table->string('visibility', 30)->default('restricted');
            $table->string('status', 30)->default('active');

            $table->text('description')->nullable();
            $table->unsignedInteger('current_version_number')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['status', 'created_at'],
                'attachments_status_date_idx'
            );
        });

        // 3. Attachment version history
        Schema::create('file_attachment_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('file_attachment_id')
                ->constrained('file_attachments')
                ->restrictOnDelete();

            $table->unsignedInteger('version_number');

            $table->foreignId('uploaded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('verification_status', 30)->default('pending');
            $table->timestamp('verified_at')->nullable();

            $table->string('original_filename', 255);
            $table->string('storage_disk', 50)->default('local');
            $table->string('storage_path', 1024);

            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->char('checksum_sha256', 64)->nullable();

            $table->text('change_note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['file_attachment_id', 'version_number'],
                'attachment_version_unique'
            );

            $table->index(
                ['file_attachment_id', 'created_at'],
                'attachment_versions_date_idx'
            );
        });

        // 4. System-wide settings
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();

            $table->string('key', 150)->unique();
            $table->string('group', 100)->default('general');

            // string, integer, boolean, json, etc.
            $table->string('value_type', 30)->default('string');
            $table->longText('value')->nullable();

            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('file_attachment_versions');
        Schema::dropIfExists('file_attachments');
        Schema::dropIfExists('audit_logs');
    }
};