<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Configuration;

interface DocCheckConfigurationProvider
{
    public function create(): DocCheckConfiguration;
}
