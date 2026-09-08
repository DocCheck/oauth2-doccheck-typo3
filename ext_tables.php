<?php

declare(strict_types=1);

use DocCheck\OAuth2DocCheckTypo3\ExtensionIdentity;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || exit;

ExtensionManagementUtility::addStaticFile(
    ExtensionIdentity::EXTENSION_KEY,
    'Configuration/TypoScript',
    'DocCheck OAuth2 for TYPO3',
);
