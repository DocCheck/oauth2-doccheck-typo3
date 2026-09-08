<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Configuration;

/**
 * Validated configuration for the DocCheck OAuth client.
 *
 * Client credentials and the redirect URI originate from environment-specific
 * TYPO3 configuration. The remaining values are safe extension settings.
 */
final readonly class DocCheckConfiguration
{
    private const ECONOMY_SCOPES = [
        'unique_id',
        'profession',
        'country',
        'language',
    ];

    private const BUSINESS_SCOPES = [
        ...self::ECONOMY_SCOPES,
        'name',
        'email',
        'address',
        'occupation_detail',
    ];

    /**
     * @param list<string> $requestedScopes
     */
    private function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $redirectUri,
        private string $licenseMode,
        private array $requestedScopes,
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
        $frontendUserProvisioningEnabled = self::boolean($configuration, 'enableFrontendUserProvisioning', false);
        $anonymousSessionFallbackAllowed = self::boolean($configuration, 'allowAnonymousSessionFallback', false);
        if ($licenseMode === 'basic' && ($frontendUserProvisioningEnabled || $anonymousSessionFallbackAllowed)) {
            throw new \InvalidArgumentException('Basic DocCheck licences do not support frontend-user provisioning or anonymous paid fallback.');
        }
        if ($frontendUserProvisioningEnabled && !in_array('unique_id', $requestedScopes, true)) {
            throw new \InvalidArgumentException('Frontend-user provisioning requires the DocCheck unique_id scope.');
        }

        return new self(
            self::requiredString($configuration, 'clientId'),
            self::requiredString($configuration, 'clientSecret'),
            self::validatedRedirectUri($configuration),
            $licenseMode,
            $requestedScopes,
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

        $allowedScopes = match ($licenseMode) {
            'economy' => self::ECONOMY_SCOPES,
            'business' => self::BUSINESS_SCOPES,
            default => [],
        };
        foreach ($requestedScopes as $scope) {
            if (!in_array($scope, $allowedScopes, true)) {
                throw new \InvalidArgumentException(sprintf('The %s scope is not available for the selected DocCheck licence.', $scope));
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
