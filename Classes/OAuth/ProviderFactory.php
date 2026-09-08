<?php

/*
 * © Copyright 2026, DocCheck agency AG, Köln, Deutschland
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

use Doccheck\OAuth2\Client\Provider\Doccheck;

final class ProviderFactory
{
    /**
     * @param array<string, mixed> $options
     */
    public function create(array $options): Doccheck
    {
        return new ConfiguredDoccheckProvider($options);
    }
}
