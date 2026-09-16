<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

/** @phpstan-type ScopeStatus 'received'|'partial'|'missing'|'invalid' */
final readonly class ScopeDiagnostic
{
    /**
     * @param ScopeStatus $status
     * @param list<string> $missingFields
     */
    public function __construct(
        private DocCheckScope $scope,
        private string $status,
        private array $missingFields,
    ) {}

    public function scope(): DocCheckScope
    {
        return $this->scope;
    }

    /** @return ScopeStatus */
    public function status(): string
    {
        return $this->status;
    }

    /** @return list<string> */
    public function missingFields(): array
    {
        return $this->missingFields;
    }
}
