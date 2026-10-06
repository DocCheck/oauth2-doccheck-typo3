<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Logging;

interface OAuthFailureLoggerInterface
{
    /**
     * Log a safe, structured OAuth failure and return its correlation reference.
     *
     * Implementations must not record exception messages, traces, request data,
     * credentials, tokens, authorization codes, or profile data.
     */
    public function error(string $route, string $stage, ?string $licenseMode, \Throwable $exception): string;
}
