<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Presentation;

use TYPO3\CMS\Core\Http\HtmlResponse;

/** Renders safe DocCheck Login errors outside the TYPO3 page-rendering pipeline. */
final readonly class InformationPageRenderer
{
    private const PAGE_TEMPLATE = <<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DocCheck Login information</title>
    <style>
        :root { color: #1f2933; background: #f5f7fa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { margin: 0; }
        .doccheck-header { height: .35rem; background: #cc0033; }
        main { max-width: 48rem; margin: 3rem auto; padding: 0 1.25rem; }
        .doccheck-info-box { padding: 1.25rem 1.5rem; background: #eef4ff; border: 1px solid #b9cff5; border-left: .35rem solid #5c6ac4; border-radius: .25rem; box-shadow: 0 8px 20px rgba(31, 41, 51, .08); }
        h1 { margin: 0 0 .75rem; color: #243b74; font-size: 1.35rem; }
        p { margin: .75rem 0; line-height: 1.55; }
        a { color: #314cb6; font-weight: 600; }
    </style>
</head>
<body>
<div class="doccheck-header"></div>
<main>
    <section class="doccheck-info-box" role="alert" aria-live="assertive">
        <h1>DocCheck Login information</h1>
        <p>%s</p>
        <p><a href="/login">Return to the login page</a></p>
    </section>
</main>
</body>
</html>
HTML;

    public function render(string $message, int $status): HtmlResponse
    {
        return new HtmlResponse(sprintf(
            self::PAGE_TEMPLATE,
            htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ), $status);
    }
}
