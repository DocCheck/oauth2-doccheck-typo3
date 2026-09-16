<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

use Doccheck\OAuth2\Client\Provider\DoccheckResourceOwner;
use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use DocCheck\OAuth2DocCheckTypo3\OAuth\ProviderFactory;
use League\OAuth2\Client\Token\AccessToken;
use TYPO3\CMS\Core\Log\LogManager;

/**
 * Retrieves user data through the official provider without persisting tokens.
 */
final readonly class UserDataService implements UserDataFetcher
{
    public function __construct(
        private ProviderFactory $providerFactory,
        private DocCheckProfileMapper $profileMapper,
        private LogManager $logManager,
    ) {}

    public function fetch(DocCheckConfiguration $configuration, AccessToken $accessToken): UserDataResult
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

        $resourceOwner = $provider->getResourceOwner($accessToken);
        if (!$resourceOwner instanceof DoccheckResourceOwner) {
            throw new \LogicException('The DocCheck provider returned an unsupported resource owner.');
        }

        $result = $this->profileMapper->map($resourceOwner, $configuration->requestedScopes());
        if ($configuration->isDebugLoggingEnabled()) {
            $logger = $this->logManager->getLogger(self::class);
            foreach ($result->scopeDiagnostics() as $diagnostic) {
                if ($diagnostic->status() !== 'received') {
                    $logger->warning('DocCheck scope response was incomplete or invalid.', [
                        'scope' => $diagnostic->scope()->value,
                        'status' => $diagnostic->status(),
                        'missingFields' => $diagnostic->missingFields(),
                    ]);
                }
            }
        }

        return $result;
    }
}
