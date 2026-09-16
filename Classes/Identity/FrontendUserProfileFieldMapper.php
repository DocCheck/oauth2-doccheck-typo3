<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

use DocCheck\OAuth2DocCheckTypo3\Configuration\DocCheckConfiguration;

/** Maps the explicitly configured, safe profile subset for a newly created FE user. */
final class FrontendUserProfileFieldMapper
{
    /** @return array<string, string> */
    public function forNewUser(DocCheckProfile $profile, DocCheckConfiguration $configuration): array
    {
        $fields = [];
        foreach ($configuration->profileFieldMappings() as $mapping) {
            if ($mapping === 'name') {
                $this->addName($fields, $profile);
            }
            if ($mapping === 'email') {
                $this->addEmail($fields, $profile);
            }
        }

        return $fields;
    }

    /** @param array<string, string> $fields */
    private function addName(array &$fields, DocCheckProfile $profile): void
    {
        if ($profile->firstName !== null) {
            $fields['first_name'] = $profile->firstName;
        }
        if ($profile->lastName !== null) {
            $fields['last_name'] = $profile->lastName;
        }
    }

    /** @param array<string, string> $fields */
    private function addEmail(array &$fields, DocCheckProfile $profile): void
    {
        if ($profile->email !== null && filter_var($profile->email, FILTER_VALIDATE_EMAIL) !== false) {
            $fields['email'] = $profile->email;
        }
    }
}
