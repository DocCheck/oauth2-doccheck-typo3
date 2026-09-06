<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use League\OAuth2\Client\Token\AccessToken;

/** Boundary for the authorization-code exchange through the provider package. */
interface TokenExchanger
{
    public function exchange(DocCheckConfiguration $configuration, string $code): AccessToken;
}
