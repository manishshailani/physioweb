<?php

declare(strict_types=1);

/**
 * Generates the bcrypt password hashes used in database/seed.mysql.sql.
 *
 * This script exists so the hashes committed in the seed file are
 * reproducible evidence, not hand-typed strings: run it and diff the
 * output against the seed file to verify. It is not part of the
 * application runtime.
 *
 * Usage: php database/generate_seed_hashes.php
 */

$passwords = [
    'admin@physioweb.test (admin user)' => 'Admin@12345',
    'priya.sharma@example.com (patient)' => 'Patient@123',
];

foreach ($passwords as $label => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    printf("%-45s %-16s %s\n", $label, $password, $hash);
    assert(password_verify($password, $hash) === true);
}
