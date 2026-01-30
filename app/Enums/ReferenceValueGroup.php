<?php

namespace App\Enums;

enum ReferenceValueGroup: string
{
    case Status = 'status';
    case OfficeDesignation = 'office_designation';
    case Category = 'category';
    case Remarks = 'remarks';

    public function label(): string
    {
        return match ($this) {
            self::Status => 'Status',
            self::OfficeDesignation => 'Office Designation',
            self::Category => 'Category',
            self::Remarks => 'Remarks',
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
