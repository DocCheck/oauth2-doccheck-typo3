<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

use DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession;
use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

final readonly class IdentityService implements IdentityEstablisher
{
    public function __construct(private FrontendUserProvisioner $frontendUserProvisioner) {}

    /**
     * @param array<string, mixed> $userData
     */
    public function establish(
        array $userData,
        DocCheckConfiguration $configuration,
        FrontendUserAuthentication $frontendUser,
        int $storagePid,
    ): void {
        $uniqueIdValue = $userData['unique_id'] ?? '';
        $uniqueId = is_string($uniqueIdValue) ? trim($uniqueIdValue) : '';
        if ($uniqueId === '') {
            throw new \RuntimeException('DocCheck did not return the required unique_id.');
        }

        try {
            $uid = $this->frontendUserProvisioner->provision($userData, $configuration, $storagePid);
            (new AuthenticatedSession($frontendUser))->establishIdentity($uid, $uniqueId, $this->profile($userData));
        } catch (\Throwable $exception) {
            if (!$configuration->isAnonymousSessionFallbackAllowed()) {
                throw $exception;
            }
            (new AuthenticatedSession($frontendUser))->establishAnonymousPaidSession();
        }
    }

    /**
     * Keep only short, user-facing values in the local session for the optional
     * status element. OAuth credentials and unrecognised provider data stay out.
     *
     * @param array<string, mixed> $userData
     * @return array<string, string>
     */
    private function profile(array $userData): array
    {
        $allowedKeys = [
            'profession',
            'profession_name',
            'country',
            'country_iso_code',
            'language',
            'language_iso_code',
            'first_name',
            'last_name',
            'email',
            'occupation_detail',
        ];
        $profile = [];
        foreach ($allowedKeys as $key) {
            $value = $userData[$key] ?? null;
            if (is_string($value) && ($value = trim($value)) !== '') {
                $profile[$key] = substr($value, 0, 255);
            }
        }

        return $profile;
    }
}
