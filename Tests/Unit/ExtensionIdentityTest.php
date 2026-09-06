<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit;

use DocCheck\OAuth2DocCheckTypo3\ExtensionIdentity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExtensionIdentityTest extends TestCase
{
    #[Test]
    public function extensionIdentityIsStable(): void
    {
        self::assertSame('oauth2_doccheck_typo3', ExtensionIdentity::EXTENSION_KEY);
        self::assertSame('doccheck/oauth2-doccheck-typo3', ExtensionIdentity::PACKAGE_NAME);
    }
}
