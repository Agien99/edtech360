<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Do not silently discard previous appointments: inspect and resolve them first.
        $duplicates = DB::table('subject_coordinator_assignments')
            ->select('subject_id')
            ->groupBy('subject_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('subject_id');
        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('More than one coordinator appointment exists for subject IDs: '
                .$duplicates->implode(', ')
                .'. Review and preserve their history before converting to global assignments. No rows were deleted.');
        }

        // MySQL may be using the composite unique key to enforce the subject FK.
        // Create a standalone subject index BEFORE removing that composite key.
        Schema::table('subject_coordinator_assignments', function (Blueprint $table) {
            $table->index('subject_id', 'subject_coordinator_subject_fk_idx');
        });

        // Drop the intake FK BEFORE dropping the column. Do this in separate
        // schema statements to keep the order explicit on MySQL.
        Schema::table('subject_coordinator_assignments', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
        });
        Schema::table('subject_coordinator_assignments', function (Blueprint $table) {
            $table->dropUnique('subject_coordinator_unique');
        });
        Schema::table('subject_coordinator_assignments', function (Blueprint $table) {
            $table->dropColumn('academic_session_id');
        });
        Schema::table('subject_coordinator_assignments', function (Blueprint $table) {
            $table->unique('subject_id', 'subject_coordinator_global_unique');
        });
    }

    public function down(): void
    {
        // Recreating intake-specific assignments requires an explicit data
        // migration, not an automatic reversal of a global appointment.
        throw new RuntimeException('Automatic rollback is intentionally disabled: global coordinator appointments have no intake to restore.');
    }
};
