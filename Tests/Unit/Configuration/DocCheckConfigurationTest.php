<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit\Configuration;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DocCheckConfigurationTest extends TestCase
{
    #[Test]
    public function basicConfigurationAllowsNoScopes(): void
    {
        $configuration = DocCheckConfiguration::fromArray($this->configuration());

        self::assertSame('basic', $configuration->licenseMode());
        self::assertSame([], $configuration->requestedScopes());
        self::assertSame(0, $configuration->defaultFrontendUserGroup());
        self::assertFalse($configuration->isFrontendUserProvisioningEnabled());
        self::assertFalse($configuration->isAnonymousSessionFallbackAllowed());
        self::assertFalse($configuration->isDebugLoggingEnabled());
    }

    #[Test]
    public function basicConfigurationRejectsScopes(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('must not request scopes');

        DocCheckConfiguration::fromArray($this->configuration(['requestedScopes' => 'unique_id']));
    }

    #[Test]
    public function economyConfigurationAcceptsOnlyItsScopes(): void
    {
        $configuration = DocCheckConfiguration::fromArray($this->configuration([
            'licenseMode' => 'economy',
            'requestedScopes' => 'unique_id, profession',
        ]));

        self::assertSame(['unique_id', 'profession'], $configuration->requestedScopes());
    }

    #[Test]
    public function paidConfigurationReadsExplicitProvisioningSettings(): void
    {
        $configuration = DocCheckConfiguration::fromArray($this->configuration([
            'licenseMode' => 'business',
            'requestedScopes' => 'unique_id',
            'enableFrontendUserProvisioning' => true,
            'allowAnonymousSessionFallback' => true,
        ]));

        self::assertTrue($configuration->isFrontendUserProvisioningEnabled());
        self::assertTrue($configuration->isAnonymousSessionFallbackAllowed());
    }

    #[Test]
    public function provisioningRequiresUniqueIdScope(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('requires the DocCheck unique_id scope');

        DocCheckConfiguration::fromArray($this->configuration([
            'licenseMode' => 'economy',
            'enableFrontendUserProvisioning' => true,
        ]));
    }

    #[Test]
    public function economyConfigurationRejectsBusinessOnlyScopes(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('not available');

        DocCheckConfiguration::fromArray($this->configuration([
            'licenseMode' => 'economy',
            'requestedScopes' => 'email',
        ]));
    }

    #[Test]
    public function configurationRequiresAnHttpsRedirectUri(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('absolute HTTPS URL');

        DocCheckConfiguration::fromArray($this->configuration(['redirectUri' => 'http://example.test/callback']));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function configuration(array $overrides = []): array
    {
        return [
            'clientId' => 'test-client-id',
            'clientSecret' => 'test-client-secret',
            'redirectUri' => 'https://example.test/doccheck/callback',
            'licenseMode' => 'basic',
            'requestedScopes' => '',
            'defaultFrontendUserGroup' => 0,
            'debugLogging' => false,
            ...$overrides,
        ];
    }
}
