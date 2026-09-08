<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use DocCheck\OAuth2DocCheckTypo3\OAuth\ProviderFactory;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Retrieves user data through the official provider without persisting tokens.
 */
final readonly class UserDataService implements UserDataFetcher
{
    public function __construct(private ProviderFactory $providerFactory) {}

    /**
     * @return array<string, mixed>
     */
    public function fetch(DocCheckConfiguration $configuration, AccessToken $accessToken): array
    {
        if ($configuration->licenseMode() === 'basic') {
            throw new \LogicException('Basic DocCheck licences must not retrieve user data.');
        }

        $provider = $this->providerFactory->create([
            'clientId' => $configuration->clientId(),
            'clientSecret' => $configuration->clientSecret(),
            'redirectUri' => $configuration->redirectUri(),
            'stateless' => false,
            'omitScope' => true,
        ]);

        $userData = [];
        foreach ($provider->getResourceOwner($accessToken)->toArray() as $key => $value) {
            if (is_string($key)) {
                $userData[$key] = $value;
            }
        }

        return $userData;
    }
}
