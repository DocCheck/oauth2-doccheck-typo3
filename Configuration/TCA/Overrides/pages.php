<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || exit;

ExtensionManagementUtility::addTCAcolumns('pages', [
    'tx_oauth2docchecktypo3_requires_auth' => [
        'label' => 'LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:pages.requiresAuth',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
]);
ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_oauth2docchecktypo3_requires_auth', '', 'after:fe_group');
