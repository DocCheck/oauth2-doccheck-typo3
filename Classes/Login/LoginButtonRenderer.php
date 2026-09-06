<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Login;

use DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession;
use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfigurationProvider;
use DocCheck\OAuth2DocCheckTypo3\OAuth\FrontendSessionOAuthTransactionStore;
use DocCheck\OAuth2DocCheckTypo3\OAuth\OAuthTransactionFactory;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Renders DocCheck's supported login web component without exposing secrets.
 */
final readonly class LoginButtonRenderer
{
    private const SCRIPT_URL = 'https://dccdn.de/static.doccheck.com/components/login-button/@latest/main.js';

    public function __construct(
        private DocCheckConfigurationProvider $configurationFactory,
        private OAuthTransactionFactory $transactionFactory,
    ) {}

    /**
     * @param array{size?: string, language?: string, returnPath?: string} $options
     */
    public function render(ServerRequestInterface $request, array $options = []): string
    {
        try {
            $configuration = $this->configurationFactory->create();
        } catch (\InvalidArgumentException) {
            return $this->configurationWarning();
        }

        $frontendUser = $request->getAttribute('frontend.user');
        if ($frontendUser instanceof FrontendUserAuthentication && (new AuthenticatedSession($frontendUser))->isAuthenticated()) {
            return '<p role="status">You are signed in with DocCheck. Use the Logout page to end this local session.</p>';
        }

        $attributes = [
            'size' => $this->size($options['size'] ?? 'medium'),
            'language' => $this->language($request, $options['language'] ?? ''),
            'loginClientId' => $configuration->clientId(),
            'redirectUri' => $configuration->redirectUri(),
        ];

        if ($configuration->licenseMode() !== 'basic') {
            if (!$frontendUser instanceof FrontendUserAuthentication) {
                return $this->configurationWarning();
            }

            $transaction = $this->transactionFactory->create(
                $this->returnPath($request, $options['returnPath'] ?? ''),
                new \DateTimeImmutable(),
            );
            (new FrontendSessionOAuthTransactionStore($frontendUser))->save($transaction);
            $attributes['state'] = $transaction->state();
            if ($configuration->requestedScopes() !== []) {
                $attributes['scope'] = implode(' ', $configuration->requestedScopes());
            }
        }

        return sprintf(
            '<script type="module" src="%s"></script><dc-login-button%s></dc-login-button>',
            self::SCRIPT_URL,
            $this->attributes($attributes),
        );
    }

    private function configurationWarning(): string
    {
        return '<p role="alert"><strong>DocCheck configuration required:</strong> This site has no complete active licence profile. Configure the client ID, server-side client secret, and exact HTTPS callback URI before using this button.</p>';
    }

    private function size(string $size): string
    {
        return in_array($size, ['small', 'medium', 'large'], true) ? $size : 'medium';
    }

    private function language(ServerRequestInterface $request, string $configuredLanguage): string
    {
        if ($configuredLanguage !== '') {
            return strtolower(substr($configuredLanguage, 0, 2));
        }

        $language = $request->getAttribute('language');
        if ($language instanceof SiteLanguage) {
            return strtolower(substr((string)$language->getLocale(), 0, 2));
        }

        return 'en';
    }

    private function returnPath(ServerRequestInterface $request, string $configuredReturnPath): string
    {
        if ($configuredReturnPath !== '') {
            return $this->safeLocalPath($configuredReturnPath) ? $configuredReturnPath : '/';
        }

        $uri = $request->getUri();
        $path = $uri->getPath();
        $query = $uri->getQuery();

        return $this->safeLocalPath($path) ? $path . ($query === '' ? '' : '?' . $query) : '/';
    }

    /**
     * @param array<string, string> $attributes
     */
    private function attributes(array $attributes): string
    {
        $result = '';
        foreach ($attributes as $name => $value) {
            $result .= sprintf(' %s="%s"', $name, htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        return $result;
    }

    private function safeLocalPath(string $path): bool
    {
        return str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_contains($path, "\r")
            && !str_contains($path, "\n");
    }
}
