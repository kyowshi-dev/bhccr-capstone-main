<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Get the authenticated user instance
 */
function user(): ?User
{
    return Auth::user();
}

/**
 * Build a driver-agnostic SQL expression that concatenates columns
 * with a separator (uses || on SQLite, CONCAT() elsewhere).
 */
function dbConcat(array $columns, string $separator = ' '): string
{
    if (DB::connection()->getDriverName() === 'sqlite') {
        return implode(" || '{$separator}' || ", $columns);
    }

    $escapedSeparator = str_replace("'", "''", $separator);

    return 'CONCAT('.implode(", '{$escapedSeparator}', ", $columns).')';
}

/**
 * Format a person's full name as "Last Suffix, First M."
 * Middle name is reduced to its initial, all parts are title-cased.
 */
function fullName(?string $last, ?string $first, ?string $middle = null, ?string $suffix = null): string
{
    $last = trim((string) $last);
    $first = trim((string) $first);
    $middle = trim((string) $middle);
    $suffix = trim((string) $suffix);

    if ($last === '' && $first === '') {
        return '';
    }

    if ($last !== '' && $suffix !== '') {
        $last .= ' '.$suffix;
    }

    $name = $last !== '' ? ucwords($last) : '';

    if ($first !== '') {
        $name .= $name !== '' ? ', ' : '';
        $name .= ucwords($first);
        if ($middle !== '') {
            $name .= ' '.mb_strtoupper(mb_substr($middle, 0, 1)).'.';
        }
    }

    return $name;
}

/**
 * Page sizes users may pick from the global "rows per page" dropdown.
 */
function pageSizeOptions(): array
{
    return [10, 15, 20, 25, 50, 100, 200, 500];
}

/**
 * Resolve the requested rows-per-page from the `per_page` query parameter,
 * falling back to the given default when absent or not an allowed size.
 */
function pageSize(int $default = 15): int
{
    $value = (int) request()->query('per_page', $default);

    if (! in_array($value, pageSizeOptions(), true)) {
        return $default;
    }

    return $value;
}
