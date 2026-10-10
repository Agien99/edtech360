<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Check expected mappings before MySQL DDL (which cannot be rolled back reliably).
        if (DB::table('classes')->where('batch_id', 1)->where('academic_session_id', '!=', 1)->exists()
            || DB::table('classes')->where('batch_id', 2)->where('academic_session_id', '!=', 2)->exists()) {
            throw new RuntimeException('The proposed development batch mappings conflict with class records.');
        }

        Schema::table('batches', function (Blueprint $table) {
            $table->foreignId('academic_session_id')->nullable()->after('id')
                ->constrained('academic_sessions')->restrictOnDelete();
            $table->unique('academic_session_id', 'batches_one_per_session_unique');
        });

        // Explicitly reviewed development records; no ID-based guesses for other environments.
        $map = [1 => 1, 2 => 2]; // session_id => batch_id
        $hasKnownDataset = DB::table('academic_sessions')->whereIn('id', [1, 2])->count() === 2
            && DB::table('batches')->whereIn('id', [1, 2])->count() === 2
            && DB::table('academic_sessions')->where('id', 1)->value('name') === '2026/2027'
            && DB::table('academic_sessions')->where('id', 2)->value('name') === '2027/2028'
            && DB::table('batches')->where('id', 1)->value('code') === 'TEST-2026'
            && DB::table('batches')->where('id', 2)->value('code') === 'F6-2027';
        if ($hasKnownDataset) {
            foreach ($map as $sessionId => $batchId) {
                $conflictingClass = DB::table('classes')->where('batch_id', $batchId)
                    ->where('academic_session_id', '!=', $sessionId)->exists();
                if ($conflictingClass) {
                    throw new RuntimeException("Cannot link batch {$batchId}: conflicting class records.");
                }
                DB::table('batches')->where('id', $batchId)->update(['academic_session_id' => $sessionId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropUnique('batches_one_per_session_unique');
            $table->dropForeign(['academic_session_id']);
            $table->dropColumn('academic_session_id');
        });
    }
};
