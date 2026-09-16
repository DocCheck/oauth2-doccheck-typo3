<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

final readonly class DocCheckAddress
{
    public function __construct(
        public ?string $areaCode,
        public ?string $street,
        public ?string $city,
        public ?string $countryIsoCode,
    ) {}
}
