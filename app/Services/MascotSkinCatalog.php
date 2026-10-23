<?php

namespace App\Services;

/// Server mirror of the mobile mascot skin catalog (mascot_skin_service.dart).
class MascotSkinCatalog
{
    public const DEFAULT_KEY = 'skin_frog_green';

    public const ITEMS = [
        'skin_frog_green' => ['name' => 'Green Frog', 'cost' => 0],
        'skin_bear_brown' => ['name' => 'Brown Bear', 'cost' => 150],
    ];

    public static function isKnown(string $key): bool
    {
        return isset(self::ITEMS[$key]);
    }

    public static function item(string $key): ?array
    {
        return self::ITEMS[$key] ?? null;
    }
}
