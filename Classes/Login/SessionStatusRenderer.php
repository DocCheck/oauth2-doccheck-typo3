<?php

declare(strict_types=1);

namespace DocCheck\OAuth2DocCheckTypo3\Login;

/**
 * Renders a human-readable, token-free summary of the local DocCheck session.
 */
final class SessionStatusRenderer
{
    /**
     * @param array{authenticated: bool, mode: 'none'|'basic'|'paid-anonymous'|'identity', frontendUserUid?: int, uniqueId?: string, profile?: array<string, string>} $status
     */
    public function render(array $status, ?string $licenseMode = null): string
    {
        $description = 'This diagnostic shows only the local DocCheck session used by this website. It never displays OAuth tokens or passwords. Any profile values shown below are cleared when you log out.';
        $licenseDetails = ['Current licence configuration' => $this->licenseLabel($licenseMode)];
        if (!$status['authenticated']) {
            return $this->section('Not signed in', 'There is no active local DocCheck session.', $description, $licenseDetails);
        }

        $message = match ($status['mode']) {
            'basic' => 'Signed in with DocCheck Access Basic. Basic authentication does not provide profile data.',
            'paid-anonymous' => 'Signed in with DocCheck Access, but no local profile was provisioned.',
            'identity' => 'Signed in with a locally provisioned DocCheck identity.',
            default => 'Signed in with DocCheck Access.',
        };
        $details = $licenseDetails;
        if ($status['mode'] === 'identity') {
            $details['DocCheck unique ID'] = $status['uniqueId'] ?? '';
            foreach ($status['profile'] ?? [] as $key => $value) {
                $details[$this->label($key)] = $value;
            }
        }

        return $this->section('Signed in', $message, $description, $details);
    }

    /**
     * @param array<string, string> $details
     */
    private function section(string $title, string $message, string $description, array $details = []): string
    {
        $markup = sprintf(
            '<section class="doccheck-session-status" aria-labelledby="doccheck-session-status"><h2 id="doccheck-session-status">DocCheck session status: %s</h2><p>%s</p><p class="doccheck-session-status__description">%s</p>',
            $this->escape($title),
            $this->escape($message),
            $this->escape($description),
        );
        if ($details !== []) {
            $markup .= '<dl>';
            foreach ($details as $label => $value) {
                if ($value !== '') {
                    $markup .= sprintf('<dt>%s</dt><dd>%s</dd>', $this->escape($label), $this->escape($value));
                }
            }
            $markup .= '</dl>';
        }

        return $markup . '</section>';
    }

    private function label(string $key): string
    {
        return match ($key) {
            'profession_name' => 'Profession',
            'country_iso_code' => 'Country',
            'language_iso_code' => 'Language',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'occupation_detail' => 'Occupation detail',
            default => ucwords(str_replace('_', ' ', $key)),
        };
    }

    private function licenseLabel(?string $licenseMode): string
    {
        return match ($licenseMode) {
            'basic' => 'Basic',
            'economy' => 'Economy',
            'business' => 'Business',
            default => 'No complete active licence profile',
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
