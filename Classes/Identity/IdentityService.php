<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

use DocCheck\OAuth2DocCheckTypo3\Access\AuthenticatedSession;
use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

final readonly class IdentityService implements IdentityEstablisher
{
    public function __construct(private FrontendUserProvisioner $frontendUserProvisioner) {}

    public function establish(
        UserDataResult $userData,
        DocCheckConfiguration $configuration,
        FrontendUserAuthentication $frontendUser,
        int $storagePid,
    ): void {
        $profile = $userData->profile();
        $uniqueId = $profile->uniqueId ?? '';
        if ($uniqueId === '') {
            throw new \RuntimeException('DocCheck did not return the required unique_id.');
        }

        try {
            $uid = $this->frontendUserProvisioner->provision($profile, $configuration, $storagePid);
            (new AuthenticatedSession($frontendUser))->establishIdentity($uid, $uniqueId, $profile->sessionProfile());
        } catch (\Throwable $exception) {
            if (!$configuration->isAnonymousSessionFallbackAllowed()) {
                throw $exception;
            }
            (new AuthenticatedSession($frontendUser))->establishAnonymousPaidSession();
        }
    }
}
