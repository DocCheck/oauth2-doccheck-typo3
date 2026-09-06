<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Exchanges a code through the official provider and deliberately retains no token.
 */
final readonly class TokenExchangeService implements TokenExchanger
{
    public function __construct(private ProviderFactory $providerFactory) {}

    public function exchange(DocCheckConfiguration $configuration, string $code): AccessToken
    {
        if ($code === '') {
            throw new \InvalidArgumentException('The authorization code must not be empty.');
        }

        $provider = $this->providerFactory->create([
            'clientId' => $configuration->clientId(),
            'clientSecret' => $configuration->clientSecret(),
            'redirectUri' => $configuration->redirectUri(),
            'stateless' => $configuration->licenseMode() === 'basic',
            'omitScope' => true,
        ]);
        $accessToken = $provider->getAccessToken('authorization_code', ['code' => $code]);
        if (!$accessToken instanceof AccessToken) {
            throw new \LogicException('The DocCheck provider returned an unsupported access-token implementation.');
        }

        return $accessToken;
    }
}
