<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;

/** Boundary for creating provider-owned authorization URLs. */
interface AuthorizationUrlGenerator
{
    public function createAuthorizationUrl(DocCheckConfiguration $configuration, ?string $state = null): string;
}
