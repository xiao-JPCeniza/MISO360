<?php

namespace App\Enums;

enum ProfileSlideTextPosition: string
{
    case Left = 'left';
    case Right = 'right';

    public function displayName(): string
    {
        return match ($this) {
            self::Left => 'Left',
            self::Right => 'Right',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(function (self $position) {
            return [$position->value => $position->displayName()];
        })->toArray();
    }
}
