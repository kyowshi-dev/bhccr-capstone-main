<?php

namespace App\Enums;

/**
 * Standard routes of administration used on prescriptions.
 *
 * Values are stored as the friendly label so the Rx line reads
 * "1 tab PO TID x 7 days" without extra mapping on display.
 */
enum MedicationRoute: string
{
    case Oral = 'PO';

    case Intravenous = 'IV';

    case Intramuscular = 'IM';

    case Subcutaneous = 'SC';

    case Topical = 'Topical';

    case Inhalation = 'Inhalation';

    case Ophthalmic = 'Ophthalmic';

    case Otic = 'Otic';

    case Rectal = 'Rectal';

    case Vaginal = 'Vaginal';

    case Sublingual = 'Sublingual';

    case Other = 'Other';

    /**
     * All accepted route values for validation.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
