<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;

/**
 * Builds DocCheck authorization URLs without bypassing the provider package.
 */
final readonly class AuthorizationService implements AuthorizationUrlGenerator
{
    public function __construct(private ProviderFactory $providerFactory) {}

    public function createAuthorizationUrl(DocCheckConfiguration $configuration, ?string $state = null): string
    {
        $isBasic = $configuration->licenseMode() === 'basic';
        if ($isBasic && $state !== null) {
            throw new \InvalidArgumentException('Basic DocCheck licences must not send an OAuth state parameter.');
        }
        if (!$isBasic && ($state === null || $state === '')) {
            throw new \InvalidArgumentException('Economy and Business DocCheck licences require an OAuth state parameter.');
        }

        $provider = $this->providerFactory->create([
            'clientId' => $configuration->clientId(),
            'clientSecret' => $configuration->clientSecret(),
            'redirectUri' => $configuration->redirectUri(),
            'stateless' => $isBasic,
            // League adds an empty scope parameter by default. DocCheck Basic
            // expressly requires that parameter to be absent, not merely empty.
            'omitScope' => $isBasic || $configuration->requestedScopes() === [],
        ]);

        $options = [];
        if (!$isBasic) {
            $options['state'] = $state;
            if ($configuration->requestedScopes() !== []) {
                $options['scope'] = $configuration->requestedScopes();
            }
        }

        return $provider->getAuthorizationUrl($options);
    }
}
