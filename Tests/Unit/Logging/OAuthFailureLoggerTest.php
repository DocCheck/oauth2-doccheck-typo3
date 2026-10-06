<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit\Logging;

use DocCheck\OAuth2DocCheckTypo3\Logging\OAuthFailureLogger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;

final class OAuthFailureLoggerTest extends TestCase
{
    #[Test]
    public function logsOnlySafeFailureContextAndReturnsAnOpaqueReference(): void
    {
        $logger = new RecordingLogger();
        $failureLogger = new OAuthFailureLogger(new RecordingLogManager($logger));

        $reference = $failureLogger->error(
            '/doccheck/callback',
            'token_exchange',
            'business',
            new \RuntimeException('authorization_code=secret-code access_token=secret-token client_secret=secret-value'),
        );

        self::assertMatchesRegularExpression('/^[a-f0-9]{24}$/', $reference);
        self::assertCount(1, $logger->records);
        self::assertSame('DocCheck OAuth request failed.', $logger->records[0]['message']);
        self::assertSame([
            'failureReference' => $reference,
            'route' => '/doccheck/callback',
            'stage' => 'token_exchange',
            'licenseMode' => 'business',
            'exceptionClass' => \RuntimeException::class,
        ], $logger->records[0]['context']);
        self::assertStringNotContainsString('secret-code', json_encode($logger->records[0]['context'], JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('secret-token', json_encode($logger->records[0]['context'], JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('secret-value', json_encode($logger->records[0]['context'], JSON_THROW_ON_ERROR));
    }
}

final class RecordingLogManager extends LogManager
{
    public function __construct(private readonly RecordingLogger $logger) {}

    public function getLogger(string $name = ''): LoggerInterface
    {
        return $this->logger;
    }
}

final class RecordingLogger extends Logger
{
    /** @var list<array{message: string, context: array<array-key, mixed>}> */
    public array $records = [];

    public function __construct()
    {
        parent::__construct('test');
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'message' => (string)$message,
            'context' => $context,
        ];
    }
}
