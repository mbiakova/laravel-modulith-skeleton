<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Modulith\Services\Modules\ModuleRegistry;
use Modulith\Testing\InteractsWithModules;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithModules;

    /** @var list<string> */
    private array $databases = [];

    /** Gives each module its own empty sqlite file, shared by its two connections, and migrates everything. */
    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->app->make(ModuleRegistry::class)->local() as $module) {
            if (! $module->hasDatabase) {
                continue;
            }

            $this->databases[] = $file = (string) tempnam(sys_get_temp_dir(), "{$module->name}-");

            foreach ([$module->connection(), $module->ownerConnection()] as $connection) {
                config()->set("database.connections.{$connection}.database", $file);
                DB::purge($connection);
            }
        }

        $this->artisan('migrate')->assertSuccessful();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        array_map(unlink(...), $this->databases);
    }
}
