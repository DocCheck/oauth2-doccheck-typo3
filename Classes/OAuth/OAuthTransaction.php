<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

/**
 * A single-use authorization transaction held server-side by a later adapter.
 */
final readonly class OAuthTransaction
{
    public function __construct(
        private string $state,
        private string $returnPath,
        private \DateTimeImmutable $expiresAt,
    ) {
        if ($state === '') {
            throw new \InvalidArgumentException('The OAuth transaction state must not be empty.');
        }
        if (!self::isSafeLocalPath($returnPath)) {
            throw new \InvalidArgumentException('The OAuth return target must be a local absolute path.');
        }
    }

    public function state(): string
    {
        return $this->state;
    }

    public function returnPath(): string
    {
        return $this->returnPath;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function matches(string $receivedState, \DateTimeImmutable $now): bool
    {
        return $now < $this->expiresAt && hash_equals($this->state, $receivedState);
    }

    private static function isSafeLocalPath(string $path): bool
    {
        return str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_contains($path, "\r")
            && !str_contains($path, "\n");
    }
}
