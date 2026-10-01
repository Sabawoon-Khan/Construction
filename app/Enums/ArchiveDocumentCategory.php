<?php

namespace App\Enums;

enum ArchiveDocumentCategory: string
{
    case Contract = 'contract';
    case Legal = 'legal';
    case Correspondence = 'correspondence';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Contract => __('Contract'),
            self::Legal => __('Legal'),
            self::Correspondence => __('Correspondence'),
            self::Other => __('Other'),
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
