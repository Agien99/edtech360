<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('student_number', 50)->nullable()->change();
            $table->string('ic_number', 12)->nullable()->unique();
        });
    }

    public function down(): void
    {
        // Existing NULL student numbers cannot safely be converted back to NOT NULL.
        // Intentionally preserve data; do not automatically reverse this migration.
        throw new \RuntimeException('Rollback requires assigning all missing student numbers manually first.');
    }
};
