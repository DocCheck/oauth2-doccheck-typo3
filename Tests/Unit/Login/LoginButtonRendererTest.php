<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit\Login;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfigurationProvider;
use DocCheck\OAuth2DocCheckTypo3\Login\LoginButtonRenderer;
use DocCheck\OAuth2DocCheckTypo3\OAuth\OAuthTransactionFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;

final class LoginButtonRendererTest extends TestCase
{
    #[Test]
    public function basicButtonUsesOfficialComponentWithoutStateOrScope(): void
    {
        $markup = $this->renderer([
            'licenseMode' => 'basic',
            'requestedScopes' => '',
        ])->render($this->request());

        self::assertStringContainsString('https://dccdn.de/static.doccheck.com/components/login-button/@latest/main.js', $markup);
        self::assertStringContainsString('<dc-login-button', $markup);
        self::assertStringContainsString('loginClientId="test-client-id"', $markup);
        self::assertStringContainsString('redirectUri="https://example.test/doccheck/callback"', $markup);
        self::assertStringNotContainsString(' state=', $markup);
        self::assertStringNotContainsString(' scope=', $markup);
    }

    #[Test]
    public function incompleteConfigurationRendersSafeWarning(): void
    {
        $renderer = new LoginButtonRenderer(
            new class () implements DocCheckConfigurationProvider {
                public function create(): DocCheckConfiguration
                {
                    throw new \InvalidArgumentException('Missing configuration.');
                }
            },
            new OAuthTransactionFactory(),
        );

        $markup = $renderer->render($this->request());

        self::assertStringContainsString('role="alert"', $markup);
        self::assertStringContainsString('client ID, server-side client secret, and exact HTTPS callback URI', $markup);
        self::assertStringNotContainsString('test-client-secret', $markup);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function renderer(array $overrides = []): LoginButtonRenderer
    {
        return new LoginButtonRenderer(
            new class (DocCheckConfiguration::fromArray([
                'clientId' => 'test-client-id',
                'clientSecret' => 'test-client-secret',
                'redirectUri' => 'https://example.test/doccheck/callback',
                'licenseMode' => 'basic',
                'requestedScopes' => '',
                ...$overrides,
            ])) implements DocCheckConfigurationProvider {
                public function __construct(private DocCheckConfiguration $configuration) {}

                public function create(): DocCheckConfiguration
                {
                    return $this->configuration;
                }
            },
            new OAuthTransactionFactory(),
        );
    }

    private function request(): ServerRequestInterface
    {
        return new ServerRequest('https://example.test/login');
    }
}
