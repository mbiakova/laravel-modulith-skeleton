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

    /** @var array{public: string, private: string}|null */
    private static ?array $jwtKeys = null;

    /** Gives each module its own empty sqlite file, shared by its two connections, and migrates everything. */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('auth.token_validation.jwt.public_key', self::jwtKeys()['public']);
        config()->set('auth.token_validation.jwt.private_key', self::jwtKeys()['private']);
        config()->set('auth.token_validation.gateway.secret', 'testing-gateway-secret');

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

    /** @return array{public: string, private: string} */
    private static function jwtKeys(): array
    {
        if (self::$jwtKeys === null) {
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $private);

            self::$jwtKeys = ['public' => openssl_pkey_get_details($key)['key'], 'private' => $private];
        }

        return self::$jwtKeys;
    }
}
