<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // This file also works if the earlier "Stage 1" migration was applied.
        if (!Schema::hasColumn('batches', 'academic_session_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->foreignId('academic_session_id')->nullable()
                    ->after('id')->constrained('academic_sessions')->restrictOnDelete();
            });
        }

        $sessions = DB::table('academic_sessions')->get();
        $batches = DB::table('batches')->get();
        $classes = DB::table('classes')->get();

        if ($sessions->isEmpty() && $batches->isEmpty()) {
            $this->ensureUniqueIndex();
            return;
        }

        // Explicitly handle ONLY the inspected development database.
        $expectedSessions = ['1' => '2026/2027', '2' => '2027/2028'];
        $expectedBatches = ['1' => 'TEST-2026', '2' => 'F6-2027'];
        $matches = $sessions->count() === 2 && $batches->count() === 2
            && $sessions->every(fn ($s) => isset($expectedSessions[$s->id]) && $expectedSessions[$s->id] === $s->name)
            && $batches->every(fn ($b) => isset($expectedBatches[$b->id]) && $expectedBatches[$b->id] === $b->code)
            && $classes->every(fn ($c) =>
                (int) $c->academic_session_id === (int) $c->batch_id
                && (int) $c->academic_session_id === 2);

        if (!$matches) {
            // Already-migrated databases and arbitrary production data must be reviewed.
            if ($batches->contains(fn ($b) => $b->academic_session_id === null)
                || $sessions->contains(fn ($s) => !$batches->contains('academic_session_id', $s->id))) {
                throw new RuntimeException('Unrecognized Academic Session/Batch data. Migration stopped without guessing associations.');
            }
        } else {
            foreach ([1 => 1, 2 => 2] as $batchId => $sessionId) {
                $existing = DB::table('batches')->where('id', $batchId)->value('academic_session_id');
                if ($existing !== null && (int) $existing !== $sessionId) {
                    throw new RuntimeException('Existing batch link differs from reviewed mapping.');
                }
                DB::table('batches')->where('id', $batchId)->update(['academic_session_id' => $sessionId]);
            }
        }
        $this->ensureUniqueIndex();
    }

    private function ensureUniqueIndex(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM batches'))
            ->pluck('Key_name')->all();
        if (!in_array('batches_academic_session_unique', $indexes, true)) {
            Schema::table('batches', fn (Blueprint $table) =>
                $table->unique('academic_session_id', 'batches_academic_session_unique'));
        }
    }

    public function down(): void
    {
        // Do not reverse any reviewed historical mapping automatically.
        // Roll back application files and restore the database backup if required.
    }
};
