<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('class_change_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_session_id')->unique()->constrained('academic_sessions')->restrictOnDelete();
            $table->dateTime('opens_at');
            $table->dateTime('closes_at');
            $table->boolean('is_enabled')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('class_change_windows'); }
};
