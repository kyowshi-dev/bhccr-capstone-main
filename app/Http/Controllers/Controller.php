<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function dbConcat(array $columns, string $separator = ' '): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return implode(" || '{$separator}' || ", $columns);
        }

        $escapedSeparator = str_replace("'", "''", $separator);

        return 'CONCAT('.implode(", '{$escapedSeparator}', ", $columns).')';
    }
}
