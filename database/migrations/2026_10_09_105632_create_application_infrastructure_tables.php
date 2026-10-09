
<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Reserved for future infrastructure tables.
        // Current drivers:
        // Cache: file
        // Queue: sync
        // Session: file
    }

    public function down(): void
    {
        // No infrastructure tables to remove.
    }
};