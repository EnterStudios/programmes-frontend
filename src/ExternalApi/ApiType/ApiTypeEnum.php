<?php
declare(strict_types = 1);

namespace App\ExternalApi\ApiType;

class ApiTypeEnum
{
    public const API_ADA = 'ADA';
    public const API_BRANDING = 'BRANDING';
    public const API_ELECTRON = 'ELECTRON';
    public const API_ORBIT = 'ORB';
    public const API_RECIPE = 'RECIPE';
    public const API_RECOMMENDATIONS = 'RECOMMENDATIONS';
    public const API_MORPH = 'MORPH';
    public const API_FAVOURITES = 'FAVOURITES';

    private const API_TYPES = [
        self::API_ADA => true,
        self::API_BRANDING => true,
        self::API_ELECTRON => true,
        self::API_ORBIT => true,
        self::API_RECIPE => true,
        self::API_RECOMMENDATIONS => true,
        self::API_MORPH => true,
        self::API_FAVOURITES => true,
    ];

    public static function isValid(string $key)
    {
        return isset(self::API_TYPES[$key]);
    }

    public static function validValues(): array
    {
        return array_keys(self::API_TYPES);
    }
}
