<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TestingDatabaseSafetyTest extends TestCase
{
    public function test_phpunit_uses_the_isolated_database(): void
    {
        $this->assertSame(
            'testing',
            app()->environment()
        );

        $this->assertSame(
            'mysql',
            config('database.default')
        );

        $this->assertSame(
            'edtech360_testing',
            config('database.connections.mysql.database')
        );

        $this->assertSame(
            'edtech360_testing',
            DB::connection()->getDatabaseName()
        );
    }
}