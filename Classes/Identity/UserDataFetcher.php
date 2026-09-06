<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use League\OAuth2\Client\Token\AccessToken;

/** Boundary for retrieving consented profile data through the provider package. */
interface UserDataFetcher
{
    /**
     * @return array<string, mixed>
     */
    public function fetch(DocCheckConfiguration $configuration, AccessToken $accessToken): array;
}
