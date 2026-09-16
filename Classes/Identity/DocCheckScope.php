<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

/**
 * Public DocCheck scopes supported by this extension and their API response
 * fields. Scope names deliberately never double as response-field names.
 */
enum DocCheckScope: string
{
    case UniqueId = 'unique_id';
    case Profession = 'profession';
    case Country = 'country';
    case Language = 'language';
    case Name = 'name';
    case Email = 'email';
    case Address = 'address';
    case OccupationDetail = 'occupation_detail';

    /** @return list<string> */
    public function responseFields(): array
    {
        return match ($this) {
            self::UniqueId => ['unique_id'],
            self::Profession => ['profession_id'],
            self::Country => ['country_iso_code', 'country_id'],
            self::Language => ['user_language'],
            self::Name => ['first_name', 'last_name'],
            self::Email => ['email'],
            self::Address => ['area_code', 'street', 'city', 'country_iso_code'],
            self::OccupationDetail => ['discipline_id', 'activity_id'],
        };
    }

    public function isAvailableFor(string $licenseMode): bool
    {
        return match ($licenseMode) {
            'economy' => in_array($this, [self::UniqueId, self::Profession, self::Country, self::Language], true),
            'business' => true,
            default => false,
        };
    }
}
