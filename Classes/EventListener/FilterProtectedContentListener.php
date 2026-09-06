<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\EventListener;

use DocCheck\OAuth2DocCheckTypo3\Access\ProtectedAccessService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\Event\ModifyRecordsAfterFetchingContentEvent;

final readonly class FilterProtectedContentListener
{
    public function __construct(private ProtectedAccessService $protectedAccessService) {}

    public function __invoke(ModifyRecordsAfterFetchingContentEvent $event): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return;
        }

        $event->setRecords(array_values(array_filter(
            $event->getRecords(),
            fn(mixed $record): bool => is_array($record) && $this->protectedAccessService->isContentVisible($record, $request),
        )));
    }
}
