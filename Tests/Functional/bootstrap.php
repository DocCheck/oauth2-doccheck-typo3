<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$testbase = new \TYPO3\TestingFramework\Core\Testbase();
$testbase->defineOriginalRootPath();
$webRoot = $testbase->getWebRoot();
$testbase->createDirectory($webRoot . 'typo3temp/var/tests');
$testbase->createDirectory($webRoot . 'typo3temp/var/transient');
