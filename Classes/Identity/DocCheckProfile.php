<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Identity;

/** A normalized, consented subset of the DocCheck user-data response. */
final readonly class DocCheckProfile
{
    public function __construct(
        public ?string $uniqueId,
        public ?int $professionId,
        public ?string $countryIsoCode,
        public ?int $countryId,
        public ?string $language,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $email,
        public ?DocCheckAddress $address,
        public ?int $disciplineId,
        public ?int $activityId,
    ) {}

    /** @return array<string, string> */
    public function sessionProfile(): array
    {
        $values = [
            'professionId' => $this->professionId,
            'countryIsoCode' => $this->countryIsoCode,
            'language' => $this->language,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'disciplineId' => $this->disciplineId,
            'activityId' => $this->activityId,
        ];
        $profile = [];
        foreach ($values as $key => $value) {
            if ($value !== null && (string)$value !== '') {
                $profile[$key] = substr((string)$value, 0, 255);
            }
        }

        return $profile;
    }
}
