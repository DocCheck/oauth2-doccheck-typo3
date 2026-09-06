<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || exit;

ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'tx_oauth2docchecktypo3_requires_auth' => [
        'label' => 'LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:ttContent.requiresAuth',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
]);
ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_oauth2docchecktypo3_requires_auth', '', 'after:fe_group');

ExtensionManagementUtility::addPlugin(
    new SelectItem(
        'select',
        'LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:ttContent.loginButton',
        'oauth2docchecktypo3_loginbutton',
        'actions-key',
        'plugins',
    ),
    'CType',
    'oauth2_doccheck_typo3',
);

$GLOBALS['TCA']['tt_content']['types']['oauth2docchecktypo3_loginbutton'] = [
    'showitem' => '--palette--;;general, --palette--;;headers, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, --palette--;;hidden',
];
