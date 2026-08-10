<?php

namespace App\Traits;

/**
 * Password hashing helper shared by user-related controllers and services.
 *
 * Hash algorithm: SHA-256 with a per-user base64-encoded salt, then
 * the raw binary digest is itself base64-encoded.
 */
trait HashesPasswords
{
    protected function passwordHash(string $password, string $salt): string
    {
        return base64_encode(hash('sha256', $password.$salt, true));
    }

    protected function generateSalt(): string
    {
        return base64_encode(random_bytes(16));
    }
}
