<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

use Doccheck\OAuth2\Client\Provider\DoccheckResourceOwner;

/** Maps the provider resource owner to extension-owned, typed data. */
final class DocCheckProfileMapper
{
    /**
     * @param list<string> $requestedScopes
     */
    public function map(DoccheckResourceOwner $resourceOwner, array $requestedScopes): UserDataResult
    {
        $response = [];
        foreach ($resourceOwner->toArray() as $key => $value) {
            if (is_string($key)) {
                $response[$key] = $value;
            }
        }
        $scopes = array_map(DocCheckScope::from(...), $requestedScopes);
        $hasScope = static fn(DocCheckScope $scope): bool => in_array($scope, $scopes, true);

        $address = $hasScope(DocCheckScope::Address) ? new DocCheckAddress(
            $this->string($response, 'area_code'),
            $this->string($response, 'street'),
            $this->string($response, 'city'),
            $this->string($response, 'country_iso_code'),
        ) : null;
        $profile = new DocCheckProfile(
            $hasScope(DocCheckScope::UniqueId) && $this->hasString($response, 'unique_id') ? $resourceOwner->getId() : null,
            $hasScope(DocCheckScope::Profession) && $this->hasInteger($response, 'profession_id') ? $resourceOwner->getProfessionId() : null,
            ($hasScope(DocCheckScope::Country) || $hasScope(DocCheckScope::Address)) && $this->hasString($response, 'country_iso_code') ? $resourceOwner->getCountryIsoCode() : null,
            $hasScope(DocCheckScope::Country) && $this->hasInteger($response, 'country_id') ? $resourceOwner->getCountryId() : null,
            $hasScope(DocCheckScope::Language) ? $this->string($response, 'user_language') : null,
            $hasScope(DocCheckScope::Name) && $this->hasString($response, 'first_name') ? $resourceOwner->getFirstName() : null,
            $hasScope(DocCheckScope::Name) && $this->hasString($response, 'last_name') ? $resourceOwner->getLastName() : null,
            $hasScope(DocCheckScope::Email) && $this->hasString($response, 'email') ? $resourceOwner->getEmail() : null,
            $address,
            $hasScope(DocCheckScope::OccupationDetail) && $this->hasInteger($response, 'discipline_id') ? $resourceOwner->getDisciplineId() : null,
            $hasScope(DocCheckScope::OccupationDetail) && $this->hasInteger($response, 'activity_id') ? $resourceOwner->getActivityId() : null,
        );

        return new UserDataResult($profile, array_map(
            fn(DocCheckScope $scope): ScopeDiagnostic => $this->diagnose($scope, $response),
            $scopes,
        ));
    }

    /** @param array<string, mixed> $response */
    private function diagnose(DocCheckScope $scope, array $response): ScopeDiagnostic
    {
        $missingFields = array_values(array_filter(
            $scope->responseFields(),
            static fn(string $field): bool => !array_key_exists($field, $response),
        ));
        if ($missingFields === $scope->responseFields()) {
            return new ScopeDiagnostic($scope, 'missing', $missingFields);
        }
        if ($missingFields !== []) {
            return new ScopeDiagnostic($scope, 'partial', $missingFields);
        }
        foreach ($scope->responseFields() as $field) {
            if (!$this->isScalarValue($response[$field])) {
                return new ScopeDiagnostic($scope, 'invalid', []);
            }
        }

        return new ScopeDiagnostic($scope, 'received', []);
    }

    /** @param array<string, mixed> $response */
    private function string(array $response, string $field): ?string
    {
        $value = $response[$field] ?? null;
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $value = trim((string)$value);

        return $value === '' ? null : $value;
    }

    /** @param array<string, mixed> $response */
    private function hasString(array $response, string $field): bool
    {
        return is_string($response[$field] ?? null);
    }

    /** @param array<string, mixed> $response */
    private function hasInteger(array $response, string $field): bool
    {
        $value = $response[$field] ?? null;

        return is_int($value) || (is_string($value) && ctype_digit($value));
    }

    private function isScalarValue(mixed $value): bool
    {
        return is_string($value) || is_int($value) || is_float($value) || is_bool($value);
    }
}
