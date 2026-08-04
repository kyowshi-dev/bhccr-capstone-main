<?php

namespace App\Helpers;

final class DateFormat
{
    /** Short human date, e.g. "Mar 4, 2026". */
    public const DATE_SHORT = 'M j, Y';

    /** Medium human date, e.g. "Mar 04, 2026". */
    public const DATE_MEDIUM = 'M d, Y';

    /** Date + 24-hour time, e.g. "Mar 04, 2026 14:30". */
    public const DATETIME_MEDIUM = 'M d, Y H:i';

    /** Date + 12-hour time, e.g. "Mar 04, 2026 2:30 PM". */
    public const DATETIME_AMPM = 'M d, Y g:i A';

    /** SQL/ISO date, e.g. "2026-03-04". */
    public const DATE_SQL = 'Y-m-d';
}
