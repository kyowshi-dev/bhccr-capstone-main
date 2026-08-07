<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;

/**
 * Encrypt a nullable string at rest, tolerating legacy plaintext values.
 *
 * If an imported database (e.g. an outdated .sql dump) contains plaintext
 * values while the column is encrypted, reading them must not crash the page.
 * The value is returned as-is and re-encrypted on the next save. The raw
 * value is never written to logs.
 */
class EncryptedString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return decrypt($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return encrypt($value);
    }
}
