<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Configuration;

use DocCheck\OAuth2DocCheckTypo3\Identity\DocCheckScope;

/**
 * Validated configuration for the DocCheck OAuth client.
 *
 * Client credentials and the redirect URI originate from environment-specific
 * TYPO3 configuration. The remaining values are safe extension settings.
 */
final readonly class DocCheckConfiguration
{
    /**
     * @param list<string> $requestedScopes
     * @param list<string> $profileFieldMappings
     */
    private function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $redirectUri,
        private string $licenseMode,
        private array $requestedScopes,
        private array $profileFieldMappings,
        private string $profileFieldSync,
        private int $defaultFrontendUserGroup,
        private bool $frontendUserProvisioningEnabled,
        private bool $anonymousSessionFallbackAllowed,
        private bool $debugLogging,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        $licenseMode = self::requiredString($configuration, 'licenseMode', 'basic');
        if (!in_array($licenseMode, ['basic', 'economy', 'business'], true)) {
            throw new \InvalidArgumentException('The DocCheck licence mode must be basic, economy, or business.');
        }

        $requestedScopes = self::scopes($configuration['requestedScopes'] ?? '');
        self::validateScopes($licenseMode, $requestedScopes);
        $profileFieldMappings = self::parseProfileFieldMappings($configuration['profileFieldMapping'] ?? '');
        $profileFieldSync = self::parseProfileFieldSync($configuration['profileFieldSync'] ?? 'create_only');
        $frontendUserProvisioningEnabled = self::boolean($configuration, 'enableFrontendUserProvisioning', false);
        $anonymousSessionFallbackAllowed = self::boolean($configuration, 'allowAnonymousSessionFallback', false);
        if ($licenseMode === 'basic' && ($frontendUserProvisioningEnabled || $anonymousSessionFallbackAllowed)) {
            throw new \InvalidArgumentException('Basic DocCheck licences do not support frontend-user provisioning or anonymous paid fallback.');
        }
        if ($frontendUserProvisioningEnabled && !in_array('unique_id', $requestedScopes, true)) {
            throw new \InvalidArgumentException('Frontend-user provisioning requires the DocCheck unique_id scope.');
        }
        self::validateProfileFieldMappings(
            $licenseMode,
            $requestedScopes,
            $profileFieldMappings,
            $frontendUserProvisioningEnabled,
        );

        return new self(
            self::requiredString($configuration, 'clientId'),
            self::requiredString($configuration, 'clientSecret'),
            self::validatedRedirectUri($configuration),
            $licenseMode,
            $requestedScopes,
            $profileFieldMappings,
            $profileFieldSync,
            self::nonNegativeInteger($configuration, 'defaultFrontendUserGroup', 0),
            $frontendUserProvisioningEnabled,
            $anonymousSessionFallbackAllowed,
            self::boolean($configuration, 'debugLogging', false),
        );
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function clientSecret(): string
    {
        return $this->clientSecret;
    }

    public function redirectUri(): string
    {
        return $this->redirectUri;
    }

    public function licenseMode(): string
    {
        return $this->licenseMode;
    }

    /**
     * @return list<string>
     */
    public function requestedScopes(): array
    {
        return $this->requestedScopes;
    }

    /** @return list<string> */
    public function profileFieldMappings(): array
    {
        return $this->profileFieldMappings;
    }

    public function profileFieldSync(): string
    {
        return $this->profileFieldSync;
    }

    public function defaultFrontendUserGroup(): int
    {
        return $this->defaultFrontendUserGroup;
    }

    public function isFrontendUserProvisioningEnabled(): bool
    {
        return $this->frontendUserProvisioningEnabled;
    }

    public function isAnonymousSessionFallbackAllowed(): bool
    {
        return $this->anonymousSessionFallbackAllowed;
    }

    public function isDebugLoggingEnabled(): bool
    {
        return $this->debugLogging;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private static function requiredString(array $configuration, string $key, ?string $default = null): string
    {
        $value = $configuration[$key] ?? $default;
        if (!is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException(sprintf('The DocCheck %s configuration value is required.', $key));
        }

        return trim($value);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private static function validatedRedirectUri(array $configuration): string
    {
        $redirectUri = self::requiredString($configuration, 'redirectUri');
        $parts = parse_url($redirectUri);
        if (!is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || !isset($parts['host'])) {
            throw new \InvalidArgumentException('The DocCheck redirectUri must be an absolute HTTPS URL.');
        }

        return $redirectUri;
    }

    /**
     * @return list<string>
     */
    private static function scopes(mixed $value): array
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('The DocCheck requestedScopes configuration value must be a string.');
        }

        $scopes = array_filter(array_map(trim(...), explode(',', $value)));
        foreach ($scopes as $scope) {
            if (!preg_match('/^[a-z_]+$/', $scope)) {
                throw new \InvalidArgumentException('DocCheck scopes may contain only lowercase letters and underscores.');
            }
        }

        return array_values(array_unique($scopes));
    }

    /**
     * @param list<string> $requestedScopes
     */
    private static function validateScopes(string $licenseMode, array $requestedScopes): void
    {
        if ($licenseMode === 'basic' && $requestedScopes !== []) {
            throw new \InvalidArgumentException('Basic DocCheck licences must not request scopes.');
        }

        foreach ($requestedScopes as $scope) {
            try {
                $isAvailable = DocCheckScope::from($scope)->isAvailableFor($licenseMode);
            } catch (\ValueError) {
                $isAvailable = false;
            }
            if (!$isAvailable) {
                throw new \InvalidArgumentException(sprintf('The %s scope is not available for the selected DocCheck licence.', $scope));
            }
        }
    }

    /** @return list<string> */
    private static function parseProfileFieldMappings(mixed $value): array
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('The DocCheck profileFieldMapping configuration value must be a string.');
        }

        $mappings = array_filter(array_map(trim(...), explode(',', $value)));
        foreach ($mappings as $mapping) {
            if (!in_array($mapping, ['name', 'email'], true)) {
                throw new \InvalidArgumentException('DocCheck profileFieldMapping supports only name and email.');
            }
        }

        return array_values(array_unique($mappings));
    }

    private static function parseProfileFieldSync(mixed $value): string
    {
        if ($value !== 'create_only') {
            throw new \InvalidArgumentException('DocCheck profileFieldSync supports only create_only.');
        }

        return $value;
    }

    /**
     * @param list<string> $requestedScopes
     * @param list<string> $profileFieldMappings
     */
    private static function validateProfileFieldMappings(
        string $licenseMode,
        array $requestedScopes,
        array $profileFieldMappings,
        bool $frontendUserProvisioningEnabled,
    ): void {
        if ($profileFieldMappings === []) {
            return;
        }
        if (!$frontendUserProvisioningEnabled) {
            throw new \InvalidArgumentException('DocCheck profileFieldMapping requires frontend-user provisioning.');
        }
        if ($licenseMode !== 'business') {
            throw new \InvalidArgumentException('DocCheck profileFieldMapping is available only for Business licences.');
        }
        foreach ($profileFieldMappings as $mapping) {
            if (!in_array($mapping, $requestedScopes, true)) {
                throw new \InvalidArgumentException(sprintf('DocCheck profileFieldMapping %s requires the %s scope.', $mapping, $mapping));
            }
        }
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private static function nonNegativeInteger(array $configuration, string $key, int $default): int
    {
        $value = filter_var($configuration[$key] ?? $default, FILTER_VALIDATE_INT);
        if (!is_int($value) || $value < 0) {
            throw new \InvalidArgumentException(sprintf('The DocCheck %s configuration value must be a non-negative integer.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private static function boolean(array $configuration, string $key, bool $default): bool
    {
        $value = filter_var($configuration[$key] ?? $default, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($value === null) {
            throw new \InvalidArgumentException(sprintf('The DocCheck %s configuration value must be boolean.', $key));
        }

        return $value;
    }
}
