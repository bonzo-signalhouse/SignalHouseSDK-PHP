<?php

namespace SignalHouse\SDK\Domains;

use SignalHouse\SDK\HttpClient;

class Onboarding
{
    private HttpClient $client;
    private bool $enableAdmin;
    public ?object $admin = null;

    public function __construct(HttpClient $client, bool $enableAdmin)
    {
        $this->client = $client;
        $this->enableAdmin = $enableAdmin;

        if ($enableAdmin) {
            $this->admin = new class($client) {
                private HttpClient $client;

                public function __construct(HttpClient $client)
                {
                    $this->client = $client;
                }

                /**
                 * Get all onboarding records (one per group). Staff-only.
                 */
                public function getOnboardings(array $options = []): array
                {
                    return $this->client->request('/group/onboarding', array_merge([
                        'method' => 'GET',
                    ], $options));
                }

                /**
                 * Get a single onboarding record by group ID. Staff-only.
                 */
                public function getOnboarding(string $groupId, array $options = []): array
                {
                    $this->client->require(['groupId' => $groupId]);
                    $safeGroupId = rawurlencode($groupId);
                    return $this->client->request("/group/onboarding/{$safeGroupId}", array_merge([
                        'method' => 'GET',
                    ], $options));
                }
            };
        }
    }
}
