<?php

declare(strict_types=1);

use DocCheck\OAuth2DocCheckTypo3\Middleware\DocCheckOAuthMiddleware;
use DocCheck\OAuth2DocCheckTypo3\Middleware\DisableAuthenticatedSessionCacheMiddleware;

return [
    'frontend' => [
        'doccheck/oauth2-doccheck-access' => [
            'target' => DocCheckOAuthMiddleware::class,
            'after' => ['typo3/cms-frontend/authentication'],
            'before' => ['typo3/cms-frontend/page-resolver'],
        ],
        'doccheck/oauth2-doccheck-access/disable-authenticated-session-cache' => [
            'target' => DisableAuthenticatedSessionCacheMiddleware::class,
            'after' => ['typo3/cms-frontend/page-argument-validator'],
            'before' => ['typo3/cms-frontend/prepare-tsfe-rendering'],
        ],
    ],
];
