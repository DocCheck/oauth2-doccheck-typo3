<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'DocCheck OAuth2 for TYPO3',
    'description' => 'TYPO3 OAuth 2.0 integration for DocCheck Access.',
    'category' => 'plugin',
    'author' => 'DocCheck',
    'author_email' => '',
    'author_company' => 'DocCheck',
    'state' => 'alpha',
    'clearCacheOnLoad' => true,
    'version' => '0.8.0-beta',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.99.99',
            'php' => '8.2.0-8.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
