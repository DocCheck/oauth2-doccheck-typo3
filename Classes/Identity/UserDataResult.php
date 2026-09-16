<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

final readonly class UserDataResult
{
    /** @param list<ScopeDiagnostic> $scopeDiagnostics */
    public function __construct(private DocCheckProfile $profile, private array $scopeDiagnostics) {}

    public function profile(): DocCheckProfile
    {
        return $this->profile;
    }

    /** @return list<ScopeDiagnostic> */
    public function scopeDiagnostics(): array
    {
        return $this->scopeDiagnostics;
    }
}
