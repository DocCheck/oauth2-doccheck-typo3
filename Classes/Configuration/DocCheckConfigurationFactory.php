<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Configuration;

use DocCheck\OAuth2DocCheckTypo3\ExtensionIdentity;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final readonly class DocCheckConfigurationFactory implements DocCheckConfigurationProvider
{
    public function __construct(private ExtensionConfiguration $extensionConfiguration) {}

    public function create(): DocCheckConfiguration
    {
        /** @var array<string, mixed> $configuration */
        $configuration = $this->extensionConfiguration->get(ExtensionIdentity::EXTENSION_KEY);

        return DocCheckConfiguration::fromArray($configuration);
    }
}
