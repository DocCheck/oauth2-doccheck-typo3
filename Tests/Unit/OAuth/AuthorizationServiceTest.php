<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit\OAuth;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use DocCheck\OAuth2DocCheckTypo3\OAuth\AuthorizationService;
use DocCheck\OAuth2DocCheckTypo3\OAuth\ProviderFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AuthorizationServiceTest extends TestCase
{
    #[Test]
    public function basicAuthorizationOmitsStateAndScope(): void
    {
        $url = $this->service()->createAuthorizationUrl($this->configuration());

        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        self::assertArrayNotHasKey('state', $query);
        self::assertArrayNotHasKey('scope', $query);
        self::assertSame('https://example.test/doccheck/callback', $query['redirect_uri']);
    }

    #[Test]
    public function economyAuthorizationUsesStateAndConfiguredScopes(): void
    {
        $url = $this->service()->createAuthorizationUrl($this->configuration([
            'licenseMode' => 'economy',
            'requestedScopes' => 'unique_id,profession',
        ]), 'secure-state');

        parse_str((string)parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame('secure-state', $query['state']);
        self::assertSame('unique_id,profession', $query['scope']);
    }

    #[Test]
    public function economyAuthorizationRequiresState(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('require an OAuth state');

        $this->service()->createAuthorizationUrl($this->configuration(['licenseMode' => 'economy']));
    }

    private function service(): AuthorizationService
    {
        return new AuthorizationService(new ProviderFactory());
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function configuration(array $overrides = []): DocCheckConfiguration
    {
        return DocCheckConfiguration::fromArray([
            'clientId' => 'test-client-id',
            'clientSecret' => 'test-client-secret',
            'redirectUri' => 'https://example.test/doccheck/callback',
            'licenseMode' => 'basic',
            'requestedScopes' => '',
            ...$overrides,
        ]);
    }
}
