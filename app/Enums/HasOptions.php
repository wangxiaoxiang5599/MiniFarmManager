<?php

namespace App\Enums;

/**
 * Shared helpers for string-backed enums that are rendered as select options.
 */
trait HasOptions
{
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
