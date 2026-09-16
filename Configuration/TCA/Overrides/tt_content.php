<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || exit;

ExtensionManagementUtility::addTCAcolumns('tt_content', [
    'tx_oauth2docchecktypo3_requires_auth' => [
        'label' => 'LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:tca.field.requireAuthentication',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
    'tx_oauth2docchecktypo3_return_path' => [
        'label' => 'LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:tca.field.returnPath',
        'description' => 'LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:tca.field.returnPath.description',
        'config' => [
            'type' => 'input',
            'eval' => 'trim',
            'max' => 2048,
            'default' => '',
        ],
    ],
    'tx_oauth2docchecktypo3_language' => [
        'label' => 'LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:tca.field.formLanguage',
        'description' => 'LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:tca.field.formLanguage.description',
        'config' => [
            'type' => 'input',
            'eval' => 'trim',
            'max' => 2,
            'default' => '',
        ],
    ],
]);
ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_oauth2docchecktypo3_requires_auth', '', 'after:fe_group');

ExtensionManagementUtility::addPlugin(
    new SelectItem(
        'select',
        'LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:tca.ctype.loginButton',
        'oauth2docchecktypo3_loginbutton',
        'actions-key',
        'plugins',
    ),
    'CType',
    'oauth2_doccheck_typo3',
);

$GLOBALS['TCA']['tt_content']['types']['oauth2docchecktypo3_loginbutton'] = [
    'showitem' => '--palette--;;general, --palette--;;headers, --div--;LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:tca.tab.doccheckAccess, tx_oauth2docchecktypo3_return_path, tx_oauth2docchecktypo3_language, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, --palette--;;hidden',
];

ExtensionManagementUtility::addPlugin(
    new SelectItem(
        'select',
        'LLL:EXT:oauth2_doccheck_typo3/Resources/Private/Language/locallang.xlf:tca.ctype.sessionStatus',
        'oauth2docchecktypo3_sessionstatus',
        'actions-document-info',
        'plugins',
    ),
    'CType',
    'oauth2_doccheck_typo3',
);

$GLOBALS['TCA']['tt_content']['types']['oauth2docchecktypo3_sessionstatus'] = [
    'showitem' => '--palette--;;general, --palette--;;headers, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, --palette--;;hidden',
];
