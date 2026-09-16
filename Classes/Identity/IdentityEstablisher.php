<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/** Boundary for mapping verified DocCheck data to local TYPO3 identity state. */
interface IdentityEstablisher
{
    public function establish(
        UserDataResult $userData,
        DocCheckConfiguration $configuration,
        FrontendUserAuthentication $frontendUser,
        int $storagePid,
    ): void;
}
