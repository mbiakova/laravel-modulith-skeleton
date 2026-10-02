<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

Artisan::command('auth:jwt-keys {--force : Replace the keys that exist}', function (): int {
    /** @var Command $this */
    $public = storage_path('jwt-public.key');
    $private = storage_path('jwt-private.key');

    if (is_file($private) && ! $this->option('force')) {
        $this->components->info('The JWT keys exist already.');

        return Command::SUCCESS;
    }

    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $pem);
    file_put_contents($private, $pem);
    file_put_contents($public, openssl_pkey_get_details($key)['key']);
    chmod($private, 0600);

    $this->components->info("JWT keys written to {$private} and {$public}.");

    return Command::SUCCESS;
})->purpose('Generate the RSA key pair iam signs JWTs with');
