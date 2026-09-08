<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\OAuth;

use Doccheck\OAuth2\Client\Provider\Doccheck;

/**
 * Keeps licence-specific protocol details inside the provider boundary.
 */
final class ConfiguredDoccheckProvider extends Doccheck
{
    private bool $omitScope;

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->omitScope = (bool)($options['omitScope'] ?? false);
        unset($options['omitScope']);

        parent::__construct($options, $collaborators);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<array-key, mixed>
     */
    protected function getAuthorizationParameters(array $options): array
    {
        $parameters = parent::getAuthorizationParameters($options);
        if ($this->omitScope) {
            unset($parameters['scope']);
        }

        return $parameters;
    }
}
