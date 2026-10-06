<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Logging;

use TYPO3\CMS\Core\Log\LogManager;

/**
 * Records middleware failures without copying sensitive OAuth input into logs.
 */
final readonly class OAuthFailureLogger implements OAuthFailureLoggerInterface
{
    public function __construct(private LogManager $logManager) {}

    public function error(string $route, string $stage, ?string $licenseMode, \Throwable $exception): string
    {
        try {
            $reference = bin2hex(random_bytes(12));
        } catch (\Throwable) {
            $reference = 'unavailable';
        }

        try {
            $this->logManager->getLogger(self::class)->error('DocCheck OAuth request failed.', [
                'failureReference' => $reference,
                'route' => $route,
                'stage' => $stage,
                'licenseMode' => $licenseMode ?? 'unknown',
                'exceptionClass' => $exception::class,
            ]);
        } catch (\Throwable) {
            // Logging must never replace the safe error response for the user.
        }

        return $reference;
    }
}
