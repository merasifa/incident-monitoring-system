<?php

namespace App\Enums;

enum IncidentSeverity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public static function values(): array
    {
        return array_map(static fn (self $severity) => $severity->value, self::cases());
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function priorityWeight(): int
    {
        return match ($this) {
            self::Critical => 4,
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
        };
    }
}
