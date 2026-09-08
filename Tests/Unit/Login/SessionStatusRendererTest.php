<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Tests\Unit\Login;

use DocCheck\OAuth2DocCheckTypo3\Login\SessionStatusRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SessionStatusRendererTest extends TestCase
{
    #[Test]
    public function basicSessionDoesNotExposeProfileDetails(): void
    {
        $markup = (new SessionStatusRenderer())->render([
            'authenticated' => true,
            'mode' => 'basic',
        ], 'basic');

        self::assertStringContainsString('Signed in with DocCheck Access Basic', $markup);
        self::assertStringContainsString('Current licence configuration', $markup);
        self::assertStringContainsString('Basic', $markup);
        self::assertStringContainsString('never displays OAuth tokens or passwords', $markup);
        self::assertStringNotContainsString('DocCheck unique ID', $markup);
    }

    #[Test]
    public function identityProfileIsEscapedBeforeRendering(): void
    {
        $markup = (new SessionStatusRenderer())->render([
            'authenticated' => true,
            'mode' => 'identity',
            'frontendUserUid' => 42,
            'uniqueId' => 'test-unique-id',
            'profile' => ['profession_name' => '<Physician>'],
        ], 'economy');

        self::assertStringContainsString('DocCheck unique ID', $markup);
        self::assertStringContainsString('Economy', $markup);
        self::assertStringContainsString('user_data_endpoint_return_values.html', $markup);
        self::assertStringContainsString('&lt;Physician&gt;', $markup);
        self::assertStringNotContainsString('<Physician>', $markup);
    }

    #[Test]
    public function missingConfigurationIsExplainedForSignedOutVisitors(): void
    {
        $markup = (new SessionStatusRenderer())->render([
            'authenticated' => false,
            'mode' => 'none',
        ]);

        self::assertStringContainsString('No complete active licence profile', $markup);
    }
}
