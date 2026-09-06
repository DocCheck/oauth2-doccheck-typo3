<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Access;

use DocCheck\OAuth2DocCheckTypo3\OAuth\BasicAuthenticationSession;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Holds authentication proof only; it never contains OAuth credentials.
 */
final readonly class AuthenticatedSession
{
    private const IDENTITY_SESSION_KEY = 'oauth2_doccheck_typo3.identity';
    private const LOGOUT_FAILURE_SESSION_KEY = 'oauth2_doccheck_typo3.logout_failure';
    private const LOGOUT_TOKEN_SESSION_KEY = 'oauth2_doccheck_typo3.logout_token';

    public function __construct(private FrontendUserAuthentication $frontendUser) {}

    public function isAuthenticated(): bool
    {
        return (new BasicAuthenticationSession($this->frontendUser))->isAuthenticated()
            || is_array($this->frontendUser->getSessionData(self::IDENTITY_SESSION_KEY));
    }

    /**
     * @param array<string, string> $profile
     */
    public function establishIdentity(int $frontendUserUid, string $uniqueId, array $profile = []): void
    {
        if ($frontendUserUid <= 0 || $uniqueId === '') {
            throw new \InvalidArgumentException('A provisioned DocCheck identity requires a frontend user and unique_id.');
        }

        $this->frontendUser->setAndSaveSessionData(self::IDENTITY_SESSION_KEY, [
            'frontendUserUid' => $frontendUserUid,
            'uniqueId' => $uniqueId,
            'profile' => $profile,
        ]);
    }

    public function establishAnonymousPaidSession(): void
    {
        $this->frontendUser->setAndSaveSessionData(self::IDENTITY_SESSION_KEY, ['anonymousPaidSession' => true]);
    }

    public function clear(): void
    {
        (new BasicAuthenticationSession($this->frontendUser))->clear();
        $this->frontendUser->setAndSaveSessionData(self::IDENTITY_SESSION_KEY, null);
        $this->frontendUser->setAndSaveSessionData(self::LOGOUT_FAILURE_SESSION_KEY, null);
        $this->frontendUser->setAndSaveSessionData(self::LOGOUT_TOKEN_SESSION_KEY, null);
    }

    public function logoutToken(): string
    {
        $token = $this->frontendUser->getSessionData(self::LOGOUT_TOKEN_SESSION_KEY);
        if (is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)) {
            return $token;
        }

        $token = bin2hex(random_bytes(32));
        $this->frontendUser->setAndSaveSessionData(self::LOGOUT_TOKEN_SESSION_KEY, $token);

        return $token;
    }

    public function hasValidLogoutToken(mixed $token): bool
    {
        $storedToken = $this->frontendUser->getSessionData(self::LOGOUT_TOKEN_SESSION_KEY);

        return is_string($token)
            && is_string($storedToken)
            && hash_equals($storedToken, $token);
    }

    public function recordLogoutFailure(): void
    {
        $this->frontendUser->setAndSaveSessionData(self::LOGOUT_FAILURE_SESSION_KEY, true);
    }

    public function consumeLogoutFailure(): bool
    {
        $failed = $this->frontendUser->getSessionData(self::LOGOUT_FAILURE_SESSION_KEY) === true;
        if ($failed) {
            $this->frontendUser->setAndSaveSessionData(self::LOGOUT_FAILURE_SESSION_KEY, null);
        }

        return $failed;
    }

    /**
     * @return array{authenticated: bool, mode: 'none'|'basic'|'paid-anonymous'|'identity', frontendUserUid?: int, uniqueId?: string, profile?: array<string, string>}
     */
    public function status(): array
    {
        $identity = $this->frontendUser->getSessionData(self::IDENTITY_SESSION_KEY);
        if (is_array($identity)) {
            if (($identity['anonymousPaidSession'] ?? false) === true) {
                return ['authenticated' => true, 'mode' => 'paid-anonymous'];
            }

            $frontendUserUid = $identity['frontendUserUid'] ?? null;
            $uniqueId = $identity['uniqueId'] ?? null;
            if (is_int($frontendUserUid) && $frontendUserUid > 0 && is_string($uniqueId) && $uniqueId !== '') {
                return [
                    'authenticated' => true,
                    'mode' => 'identity',
                    'frontendUserUid' => $frontendUserUid,
                    'uniqueId' => $uniqueId,
                    'profile' => $this->profile($identity['profile'] ?? []),
                ];
            }
        }

        if ((new BasicAuthenticationSession($this->frontendUser))->isAuthenticated()) {
            return ['authenticated' => true, 'mode' => 'basic'];
        }

        return ['authenticated' => false, 'mode' => 'none'];
    }

    /**
     * @return array<string, string>
     */
    private function profile(mixed $profile): array
    {
        if (!is_array($profile)) {
            return [];
        }

        $result = [];
        foreach ($profile as $key => $value) {
            if (is_string($key) && is_string($value) && $key !== '' && $value !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
